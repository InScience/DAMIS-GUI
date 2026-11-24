#!/usr/bin/env bash
# Smoke test of the running Docker Compose stack, through Apache like a browser. Run in the php container:
#
#   docker compose exec -T -u www-data -e SMOKE_USER=... -e SMOKE_PASSWORD=... php bash tests/smoke/smoke.sh
#
# SMOKE_USER must be an administrator (ROLE_ADMIN, ROLE_CONFIRMED). Checks: console commands, public pages,
# login, every parameterless GET route, the prod environment, dataset upload (CSV, ARFF, XLSX) and the six
# conversions. The uploaded datasets are deleted at the end. Exit code 1 if any check fails.
set -u
cd "$(dirname "$0")/../.."
B=${SMOKE_BASE_URL:-http://apache}
: "${SMOKE_USER:?set SMOKE_USER (an administrator)}" "${SMOKE_PASSWORD:?set SMOKE_PASSWORD}"
W=$(mktemp -d /tmp/smoke.XXXXXX)
J=$W/cookies.jar
TAG="smoke-$(date +%s)-$$"
trap 'rm -rf "$W"' EXIT
fail=0
check() { # label expected-regex actual
    if [[ "$3" =~ ^($2)$ ]]; then echo "  ok   $1 -> $3"; else echo "  FAIL $1 -> $3 (expected $2)"; fail=1; fi
}
http() { curl -s -c "$J" -b "$J" -o /dev/null -w '%{http_code}' -H "Referer: $B/" "$@"; }

echo "== console"
for c in "about" "lint:container" "lint:twig src templates" "lint:yaml config" "debug:router" "experiment:execute --help" "app:cleanup-unconfirmed-users --help"; do
    # shellcheck disable=SC2086 # $c is a command with its arguments
    if php bin/console $c > "$W/console.log" 2>&1; then echo "  ok   $c"; else echo "  FAIL $c"; tail -5 "$W/console.log"; fail=1; fi
done

echo "== anonymous pages"
for u in / /login /register/ /resetting/request /page/faq.html /page/help.html /lt.html /en.html /css/midas.css /js/fos_js_routes.js /build/manifest.json; do
    check "$u" "200|302" "$(http "$B$u")"
done
check "/experiments.html (anonymous -> login)" "302" "$(curl -s -o /dev/null -w '%{http_code}' "$B/experiments.html")"

echo "== login"
T=$(curl -s -c "$J" -b "$J" "$B/login" | grep -oE 'name="_csrf_token" value="[^"]+"' | sed -E 's/.*value="([^"]+)"/\1/')
# the password goes through stdin, not the curl command line (visible in ps)
check "login_check" "302" "$(printf '%s' "$SMOKE_PASSWORD" | http --data-urlencode "_username=$SMOKE_USER" --data-urlencode "_password@-" --data-urlencode "_csrf_token=$T" "$B/login_check")"
check "logged in (/profile/)" "200" "$(http "$B/profile/")"

echo "== all parameterless GET routes (administrator)"
routes=$(php bin/console debug:router --format=json 2>/dev/null | php -r '
foreach (json_decode(stream_get_contents(STDIN), true) as $name => $r) {
    if (str_starts_with($name, "_") || str_contains($r["path"], "{")) continue;
    if ($r["method"] !== "ANY" && !str_contains($r["method"], "GET")) continue;
    if (in_array($name, ["fos_user_security_logout", "fos_user_security_check", "cron_export"])) continue;
    echo $r["path"], PHP_EOL;
}')
for u in $routes; do
    case "$u" in
        /experiment/chart/dataset.html) exp="400" ;;                # needs a dataset_url parameter
        /experiment/component/existingMidasFile.html) exp="403" ;;  # needs a MIDAS session
        *) exp="200|302" ;;
    esac
    check "$u" "$exp" "$(http "$B$u")"
done

echo "== prod environment"
for c in "cache:clear" "lint:container" "about"; do
    if APP_ENV=prod APP_DEBUG=0 php bin/console $c > "$W/prod.log" 2>&1; then echo "  ok   prod $c"; else echo "  FAIL prod $c"; tail -5 "$W/prod.log"; fail=1; fi
done

echo "== dataset upload + conversions"
cat > "$W/iris.csv" <<'CSV'
sepallength,sepalwidth,petallength,petalwidth,class
5.1,3.5,1.4,0.2,Iris-setosa
4.9,3.0,1.4,0.2,Iris-setosa
7.0,3.2,4.7,1.4,Iris-versicolor
6.3,3.3,6.0,2.5,Iris-virginica
CSV
cp tests/fixtures/irisc.arff "$W/iris.arff"
php -r 'require "vendor/autoload.php";
$s = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$s->getActiveSheet()->fromArray([["a","b","label"],[1.5,2,"x"],[3,4.25,"y"]]);
(new PhpOffice\PhpSpreadsheet\Writer\Xlsx($s))->save($argv[1]);' "$W/lentele.xlsx"

for f in iris.csv iris.arff lentele.xlsx; do
    check "upload $f" "302" "$(http -F "datasets_newtype[datasetTitle]=$TAG ${f%%.*}" \
        -F "datasets_newtype[datasetDescription]=smoke test" -F "datasets_newtype[file]=@$W/$f" "$B/datasets/create.html")"
done

# the application stores the titles with underscores for spaces
ids=$(php bin/console dbal:run-sql "SELECT DatasetID FROM dataset WHERE DatasetTitle LIKE '$TAG%' ORDER BY DatasetID" 2>/dev/null | grep -oE '^\s*[0-9]+\s*$' | tr -d ' ')
[ "$(echo "$ids" | grep -c .)" = 3 ] && echo "  ok   3 datasets stored" || { echo "  FAIL datasets stored: $(echo $ids)"; fail=1; }
for id in $ids; do
    for fmt in csv arff txt tab xls xlsx; do
        out=$W/export-$id.$fmt
        check "dataset $id -> $fmt" "200" "$(curl -s -c "$J" -b "$J" -o "$out" -w '%{http_code}' "$B/datasets/$id/convert_$fmt")"
    done
    grep -q "sepallength\|^a," "$W/export-$id.csv" && echo "  ok   dataset $id csv has its header" || { echo "  FAIL dataset $id csv: $(head -c 200 "$W/export-$id.csv")"; fail=1; }
done
# Excel exports must be readable again by PhpSpreadsheet
for f in "$W"/export-*.xls "$W"/export-*.xlsx; do
    [ -e "$f" ] || continue
    if php -r 'require "vendor/autoload.php"; exit(count(PhpOffice\PhpSpreadsheet\IOFactory::load($argv[1])->getActiveSheet()->toArray()) > 1 ? 0 : 1);' "$f"; then
        echo "  ok   read back $(basename "$f")"
    else
        echo "  FAIL read back $(basename "$f")"; fail=1
    fi
done

echo "== delete the uploaded datasets"
list=$(echo $ids | sed 's/ /,/g')
[ -n "$list" ] || { echo "  FAIL nothing to delete"; fail=1; }
check "delete" "302" "$(http --data-urlencode "file-delete-list=[$list]" "$B/datasets/delete.html")"
left=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM dataset WHERE DatasetTitle LIKE '$TAG%'" 2>/dev/null | grep -oE '^\s*[0-9]+\s*$' | tr -d ' ')
check "datasets left" "0" "$left"

[ $fail = 0 ] && echo "RESULT: all checks passed" || echo "RESULT: some checks FAILED"
exit $fail

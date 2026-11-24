<?php
// Calls every DamisService operation with PHP SoapClient (same client options as ExecuteExperimentCommand),
// downloads the result files and checks them, plus three fault cases. Run in the php container:
//
//   docker compose exec -T -u www-data php php tests/smoke/damis-service.php
//
// The input files (tests/fixtures/iris.arff, irisc.arff) are copied to public/uploads/smoke-<pid>/ for the
// time of the run, so the service can download them from Apache (DAMIS_DATA_HOST). Exit code 1 on failure.

$wsdl = getenv('DAMIS_SERVICE_WSDL') ?: 'http://damis-service/cgi-bin/DamisService.cgi?wsdl';
$project = dirname(__DIR__, 2);
$dir = 'uploads/smoke-'.getmypid();
$base = rtrim(getenv('DAMIS_DATA_HOST') ?: 'http://apache', '/').'/'.$dir.'/';

mkdir($project.'/public/'.$dir, 0775, true);
foreach (['iris.arff', 'irisc.arff'] as $file) {
    copy($project.'/tests/fixtures/'.$file, $project.'/public/'.$dir.'/'.$file);
}
register_shutdown_function(static function () use ($project, $dir): void {
    array_map('unlink', glob($project.'/public/'.$dir.'/*'));
    rmdir($project.'/public/'.$dir);
});

$X = $base.'iris.arff';   // 4 numeric attributes, 150 objects
$XC = $base.'irisc.arff'; // + class {A,B,C}

ini_set('default_socket_timeout', 600);
$client = new SoapClient($wsdl, ['trace' => 1, 'connection_timeout' => 600, 'cache_wsdl' => WSDL_CACHE_NONE]);

// parameters in WSDL message order (SoapClient maps them by position)
$calls = [
    ['PCA',            [$X, false, 2, 60]],
    ['PCA',            [$X, true, 90, 60], 'projType=true (keep 90% variance)'],
    ['SMACOFMDS',      [$X, 2, 100, 0.0001, false, 2, 60]],
    ['SMACOFMDS',      [$X, 2, 100, 0.0001, true, 1, 60], 'zeidel'],
    ['DMA',            [$X, 2, 100, 0.0001, 10, 60]],
    ['RELMDS',         [$X, 2, 100, 0.0001, 10, 1, 60]],
    ['SAMANN',         [$X, 2, 100, 10, 10, 0.1, 1, 60]],
    ['SOM',            [$X, 3, 3, 10, 1, 60]],
    ['SOMMDS',         [$X, 3, 3, 10, 50, 0.0001, 2, 60]],
    ['MLP',            [$XC, 5, 0, 80, false, 100, 1, 60]],
    ['MLP',            [$XC, 5, 3, 5, true, 100, 2, 60], 'k-fold'],
    ['DF',             [$XC, 0.5, 80, 20, 60]],
    ['KMEANS',         [$X, 3, 100, 60]],
    ['STATPRIMITIVES', [$X, 60]],
    ['CLEANDATA',      [$X, 60]],
    ['FILTERDATA',     [$X, true, 3, 1, 60]],
    ['SPLITDATA',      [$X, true, 70, 30, 60]],
    ['TRANSPOSEDATA',  [$X, 60]],
    ['NORMDATA',       [$X, true, 0, 1, 60]],
    // expected faults
    ['PCA',            [$X, false, 0, 60], 'FAULT expected: d = 0'],
    ['PCA',            [$base.'missing.arff', false, 2, 60], 'FAULT expected: missing input file'],
    ['SMACOFMDS',      [$X, 2, 100, 0.0001, false, 999, 60], 'FAULT expected: too many processors'],
];

$fail = 0;
foreach ($calls as $c) {
    [$fn, $args] = $c;
    $note = $c[2] ?? '';
    $expectFault = str_starts_with($note, 'FAULT');
    $t = microtime(true);
    try {
        $r = $client->__soapCall($fn, $args);
        $secs = microtime(true) - $t;
        $rows = [];
        foreach (['Y', 'Yalt'] as $k) {
            if (!empty($r[$k])) {
                $body = @file_get_contents($r[$k]);
                $rows[] = $k.'='.(false === $body ? 'DOWNLOAD FAILED' : count(preg_grep('/^\s*[^@%\s]/', explode("\n", $body))).' rows');
            }
        }
        $ok = !$expectFault && $rows && !preg_grep('/FAILED/', $rows);
        $fail += $ok ? 0 : 1;
        printf("%-4s %-15s %5.1fs calcTime=%-8s algErr=%-12s %s %s\n", $ok ? 'OK' : 'FAIL', $fn, $secs,
            round($r['calcTime'] ?? -1, 3), isset($r['algorithmError']) ? round($r['algorithmError'], 6) : '-', implode(' ', $rows), $note);
    } catch (SoapFault $e) {
        $ok = $expectFault;
        $fail += $ok ? 0 : 1;
        printf("%-4s %-15s FAULT %s | %s %s\n", $ok ? 'OK' : 'FAIL', $fn, $e->getMessage(),
            str_replace("\n", ' / ', substr(is_string($e->detail ?? null) ? $e->detail : json_encode($e->detail ?? ''), 0, 160)), $note);
        if (!$ok) {
            echo '     last response: ', substr((string) $client->__getLastResponse(), 0, 400), "\n";
        }
    }
}
echo $fail ? "$fail FAILED\n" : "ALL OK\n";
exit($fail ? 1 : 0);

#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

echo "=== DAMIS Vendor Update Script ==="
echo ""

# 1. Check PHP version
REQUIRED_PHP="8.4"
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
if [ "$(printf '%s\n' "$REQUIRED_PHP" "$PHP_VERSION" | sort -V | head -n1)" != "$REQUIRED_PHP" ]; then
    echo "ERROR: PHP >= $REQUIRED_PHP required, found $PHP_VERSION"
    exit 1
fi
echo "[OK] PHP $PHP_VERSION"

# 2. Check composer is available
if ! command -v composer &> /dev/null; then
    echo "ERROR: composer not found in PATH"
    exit 1
fi
echo "[OK] Composer found"

# 3. Backup composer.lock
if [ -f composer.lock ]; then
    cp composer.lock composer.lock.bak
    echo "[OK] Backed up composer.lock -> composer.lock.bak"
else
    echo "[WARN] No composer.lock found, fresh install will be performed"
fi

# 4. Run composer update
echo ""
echo "--- Running composer update ---"
if composer update --no-interaction; then
    echo ""
    echo "[OK] Composer update completed successfully"
else
    echo ""
    echo "ERROR: Composer update failed!"
    if [ -f composer.lock.bak ]; then
        echo "Restoring previous composer.lock..."
        mv composer.lock.bak composer.lock
        composer install --no-interaction
        echo "Rolled back to previous state."
    fi
    exit 1
fi

# 5. Clear Symfony cache
echo ""
echo "--- Clearing Symfony cache ---"
php bin/console cache:clear --no-interaction
echo "[OK] Cache cleared"

# 6. Clean up backup on success
if [ -f composer.lock.bak ]; then
    rm composer.lock.bak
fi

echo ""
echo "=== Update completed successfully ==="

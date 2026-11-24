<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Fresh schema in the test database (<database>_test) for every run, built from the entity mapping. Set
// DAMIS_TEST_KEEP_SCHEMA=1 to skip it when re-running single tests.
if (!getenv('DAMIS_TEST_KEEP_SCHEMA')) {
    $console = escapeshellarg(dirname(__DIR__).'/bin/console');
    foreach (['doctrine:schema:drop --force --full-database', 'doctrine:schema:create'] as $command) {
        passthru(sprintf('APP_ENV=test php %s %s --no-interaction --quiet', $console, $command), $exitCode);
        if (0 !== $exitCode) {
            fwrite(STDERR, "tests/bootstrap.php: '$command' failed (exit code $exitCode)\n");
            exit(1);
        }
    }
}

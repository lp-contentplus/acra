<?php

if (!defined('APPLICATION_ENV')) {
    define('APPLICATION_ENV', getenv('APPLICATION_ENV') ?: 'production');
}

if (APPLICATION_ENV == 'dev') {
    error_reporting(\E_ALL);
    ini_set('display_errors', true);
} else {
    error_reporting(0);
    ini_set('display_errors', false);
}

require __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$config = require __DIR__ . '/../config.php';

$varDir = __DIR__ . '/../var/';
if (!is_dir($varDir)) {
    throw new \Exception('Missing var directory');
}

$logFile = "$varDir/acra.log";
if (!touch($logFile)) {
    throw new \Exception('Log file not writeable');
}

// create a log channel
$log = new Logger('acra');
$log->pushHandler(new StreamHandler($logFile, Logger::WARNING));

$input = file_get_contents('php://input');
$service = $_GET['as'] ?? null;

if ($_GET['test'] ?? '') {
    $log->error("[info] Test entry");
}

if (!$input || !$service) {
    exit();
}

$input = json_decode($input, true);
if ($input) {
    // add records to the log
    $log->error("[$service]", $input);

    try {
        $connection = DriverManager::getConnection($config['connection']);
        $connection->executeUpdate(
            'INSERT INTO log (log) VALUES (?)',
            [
                json_encode([
                    $input
                ])
            ]
        );
    } catch (\Throwable $e) {
        $log->error("[$service] Fatal error: " . $e->__toString());
    }
} else {
    $log->warning("[$service] Invalid request received");
}

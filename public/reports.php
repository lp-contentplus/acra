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

$page = 0;
$limit = 25;

$connection = DriverManager::getConnection($config['connection']);
$results = $connection->executeQuery(
    'SELECT * FROM log LIMIT ? OFFSET ?',
    [
        $limit, $limit * $page
    ]
);
$data = $results->fetchAll();

function escape($value)
{
    if (\PHP_VERSION_ID >= 50400) {
        $flags = ENT_QUOTES | ENT_SUBSTITUTE;
    } else {
        $flags = ENT_QUOTES;
    }

    // Numbers and Boolean values get turned into strings which can cause problems
    // with type comparisons (e.g. === or is_int() etc).
    return is_string($value) ? htmlspecialchars($value, $flags, 'utf-8', false) : $value;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ACRA reports</title>
    <style>
        html, body {
            font-size: 16px;
        }

        .report {
            border-bottom: 1px solid #ccc;
            margin: 1rem;
            font-family: Arial, sans-serif;
        }

        .roll {
            overflow-x: hidden;
            overflow-y: scroll;
            height: 80px;
            margin: 1rem 0;
            border: 1px solid #ddd;
            padding: .5rem;
            font-family: "Lucida Console", Monaco, monospace;
            resize: vertical;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>

<?php foreach ($data as $row): ?>
    <?php $report = json_decode($row['log'], true); ?>
    <?php $report = isset($report[0]) ? $report[0] : $report; ?>
    <div class="report">
        <h3>
            <?php echo escape($report['BUILD_CONFIG']['APPLICATION_ID']) ?>
            <?php
            echo (new \DateTime($report['USER_CRASH_DATE']))->format(\DateTime::ISO8601)
            ?>
        </h3>
        <div class="roll">
            <?php echo nl2br(escape($report['LOGCAT'])); ?>
        </div>
        <div class="roll">
            <?php echo nl2br(escape($report['STACK_TRACE'])); ?>
        </div>
    </div>
<?php endforeach ?>

</body>
</html>

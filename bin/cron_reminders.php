<?php

declare(strict_types=1);

use Rent\Application;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    fwrite(STDERR, "Dependencies are not installed. Run: composer install\n");
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Support/helpers.php';

$config = require dirname(__DIR__) . '/config.php';
$app = new Application($config, false);

$result = $app->reminderService()->sendFiveDaysReminders();

$line = sprintf(
    "[%s] target=%s total=%d sent=%d skipped=%d\n",
    app_now((string) app_config($config, 'app.timezone', 'Europe/Moscow'))->format('Y-m-d H:i:s'),
    $result['target_date'],
    $result['total_due'],
    $result['sent'],
    $result['skipped']
);

echo $line;
$app->logger()->info('Cron reminders finished', $result);

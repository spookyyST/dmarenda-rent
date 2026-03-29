<?php

declare(strict_types=1);

use Rent\Application;
use Rent\Http\Request;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (!is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    http_response_code(500);
    echo 'Dependencies are not installed. Run: composer install';
    exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Support/helpers.php';

$config = require dirname(__DIR__) . '/config.php';
$app = new Application($config, true);

$request = Request::capture((string) app_config($config, 'app.base_path', '/rent'));
$response = $app->router()->dispatch($request);
$response->send();

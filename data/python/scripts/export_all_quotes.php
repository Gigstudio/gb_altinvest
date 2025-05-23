#!/usr/bin/env php
<?php
// Скрипт создан для тестирования записи в json файл

define('PATH_ROOT', '/var/www/html/');

use GIG\Core\Application;
use GIG\Core\Config;
use GIG\Domain\Services\TradernetService;

require_once PATH_ROOT . 'bootstrap.php';

$config = new Config();
$app = new Application($config);

$symbols = $app->getConfig('tickers', []);

foreach ($symbols as $symbol => $name) {
    try {
        $quotes = $app->getTradernetService()->getQuotes($symbol, dateFrom: '01.01.2022 00:00', dateTo: date('d.m.Y H:i'));
        TradernetService::saveAsJson($quotes, $symbol);
        echo "Exported: $symbol\n";
    } catch (Throwable $e) {
        echo "Error for $symbol: " . $e->getMessage() . "\n";
    }
}
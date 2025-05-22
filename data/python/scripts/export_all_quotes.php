#!/usr/bin/env php
<?php
define('PATH_ROOT', '/var/www/html/');

use GIG\Core\Application;
use GIG\Core\Config;
use GIG\Domain\Services\TradernetService;

require_once PATH_ROOT . 'bootstrap.php';

$config = new Config();
$app = new Application($config);

// try {
//     $repo = new PercoUserRepository();
//     $repo->refreshPercoUsers();
// } catch (\Throwable $e) {
//     if (isset($repo)) $repo->setPercoSyncDone();
//     file_put_contents(PATH_LOGS . 'perco_sync_error.log', $e . "\n", FILE_APPEND);
// }
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
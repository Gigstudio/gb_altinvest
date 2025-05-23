<?php
return [
    'GET' => [
        '/' => [\GIG\Presentation\Controller\HomeController::class, 'index'],
        '/api_test' => [\GIG\Presentation\Controller\HomeController::class, 'testTradernetApi'],
        '/altdata' => [\GIG\Presentation\Controller\HomeController::class, 'altdata'],
        '/analytics' => [\GIG\Presentation\Controller\HomeController::class, 'analytics'],
        // '/login' => [\GigReportServer\Pages\Controllers\AuthController::class, 'login'],
    ],
    'POST' => [
        // '/api_test' => [\GIG\Presentation\Controller\HomeController::class, 'api_test'],
        // '/login' => [\GigReportServer\Pages\Controllers\AuthController::class, 'login'],
    ],
];

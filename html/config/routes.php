<?php
return [
    'GET' => [
        '/' => [\GIG\Presentation\Controller\HomeController::class, 'index'],
        '/api_test' => [\GIG\Presentation\Controller\HomeController::class, 'testTradernetApi'],
        '/vacancies' => [\GIG\Presentation\Controller\HomeController::class, 'altdata'],
        '/reports' => [\GIG\Presentation\Controller\HomeController::class, 'reports'],
        // '/login' => [\GigReportServer\Pages\Controllers\AuthController::class, 'login'],
    ],
    'POST' => [
        '/api_test' => [\GIG\Presentation\Controller\HomeController::class, 'api_test'],
        // '/login' => [\GigReportServer\Pages\Controllers\AuthController::class, 'login'],
    ],
];

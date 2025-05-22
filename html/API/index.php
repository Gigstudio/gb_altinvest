<?php
require_once __DIR__ . '/../bootstrap.php';

use GIG\Core\ErrorHandler;
use GIG\Core\Config;
use GIG\Core\Application;
use GIG\Domain\Exceptions\GeneralException;

ErrorHandler::register();

$config = new Config();
$app = new Application($config);

header('Content-Type: application/json; charset=UTF-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $module = $input['module'] ?? null;

    if (!$module) {
        throw new GeneralException(
            'Команда не передана',
            400,
            [
                'reason' => 'missing_module',
                'detail' => 'Входящий запрос не содержит ключ module'
            ]
        );
    }

    $handlerFile = __DIR__ . "/handlers/{$module}.php";

    if (!file_exists($handlerFile)) {
        throw new GeneralException(
            'Неизвестная команда',
            404,
            [
                'reason' => 'unknown_module',
                'detail' => "Не найден обработчик API для модуля: $module"
            ]
        );
    }

    require_once $handlerFile;

} catch (Throwable $e) {
    ErrorHandler::handleException($e);
}
exit;

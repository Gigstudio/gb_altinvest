<?php
use GIG\Core\Config;
use GIG\Domain\Exceptions\GeneralException;

defined('_RUNKEY') or die;

$action = $input['action'] ?? null;
$key = $input['key'] ?? ($input['data']['key'] ?? null);
$data = $input['data'] ?? [];

switch ($action) {
    case 'get':
        try {
            // Без ключа — отдать весь конфиг (ОСТОРОЖНО: можно ограничить права доступа)
            if ($key === null) {
                $result = Config::get();
            } else {
                $result = Config::get($key);
            }
            echo json_encode([
                'status' => 'success',
                'result' => $result
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            throw new GeneralException(
                "Ошибка получения конфигурации",
                500,
                [
                    'detail' => $e->getMessage(),
                    'reason' => 'config_access_error'
                ]
            );
        }
        break;

    default:
        throw new GeneralException(
            'Неизвестное действие',
            400,
            [
                'detail' => "Получен неизвестный action: $action",
                'reason' => 'invalid_action'
            ]
        );
}

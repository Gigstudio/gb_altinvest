<?php
use GIG\API\Controller\PercoController;
use GIG\Domain\Exceptions\GeneralException;

defined('_RUNKEY') or die;

$action = $input['action'] ?? null;
$data = $input['data'] ?? [];

$controller = new PercoController();

switch ($action) {
    case 'lookup':
        $controller->lookup($data);
        break;

    case 'refresh':
        $controller->refresh();
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

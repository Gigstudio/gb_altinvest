<?php
use GIG\API\Controller\ConsoleController;
use GIG\Domain\Exceptions\GeneralException;

defined('_RUNKEY') or die;

$action = $input['action'] ?? null;
$data = $input['data'] ?? [];

$controller = new ConsoleController();

switch ($action) {
    case 'get':
        $controller->get($data);
        break;

    case 'clear':
        $controller->clear();
        break;

    case 'add':
        $controller->add($data);
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

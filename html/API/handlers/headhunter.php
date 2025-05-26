<?php
use GIG\API\Controller\HhController;
use GIG\Domain\Exceptions\GeneralException;

defined('_RUNKEY') or die;

$action = $input['action'] ?? null;
$data = $input['data'] ?? [];

$controller = new HhController();

switch ($action) {
    case 'getVacancies':
        $controller->getVacancies($data);
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

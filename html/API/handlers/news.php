<?php
use GIG\API\Controller\TradernetController;
use GIG\Domain\Exceptions\GeneralException;

defined('_RUNKEY') or die;

$action = $input['action'] ?? null;
$data = $input['data'] ?? [];

$controller = new TradernetController();

switch ($action) {
    case 'getNews':
        $controller->getNews($data);
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

<?php
use GIG\API\Controller\AuthController;

defined('_RUNKEY') or die;

$action = $input['action'] ?? null;
$data = $input['data'] ?? [];

$controller = new AuthController();

switch ($action) {
    case 'getmodal':
        include PATH_VIEWS . 'login.php';
        break;

    case 'login':
        $controller->login($data);
        break;

    case 'register':
        $controller->register($data);
        break;

    case 'logout':
        $controller->logout();
        break;

    case 'checkLogin':
        $controller->checkLoginSimple($data);
        break;

    case 'checkLoginExtended':
        $controller->checkLoginExtended($data);
        break;

    default:
    throw new \GIG\Domain\Exceptions\GeneralException('Неизвестное действие', 400, [
        'reason' => 'invalid_action',
        'detail' => 'В auth.php передано неизвестное действие: ' . $action
    ]);
}

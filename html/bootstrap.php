<?php
declare(strict_types=1);

// Защита
define('_RUNKEY', 1);

// Разделитель директорий
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// Абсолютный путь до корня проекта
defined('ABSPATH') or define('ABSPATH', __DIR__ . DS);

// Сессии
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Отображение всех ошибок в режиме разработки
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Установка временной зоны, если не задана
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Подготовка серверных переменных
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
}

if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
}

// Обработка HTTPS/SSL и проксированных заголовков
$_SERVER['HTTPS'] = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
);

// Обработка IP-адреса через прокси
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $_SERVER['REMOTE_ADDR'] = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}
// Подключение служебных функций
if (file_exists(ABSPATH . 'app/Core/functions.php')) {
    require_once ABSPATH . 'app/Core/functions.php';
} else {
    die('No required file (functions.php) found. Stopped.');
}

// Подключение констант проекта
if (file_exists(ABSPATH . 'config/defines.php')) {
    require_once ABSPATH . 'config/defines.php';
} else {
    die('No required file (defines.php) found. Stopped.');
}

// Автолоадер
$autoloaderFile = PATH_CORE . 'Autoloader.php';
if (!file_exists($autoloaderFile)) {
    die('Critical error: Autoloader is missing.');
}
require_once $autoloaderFile;

// Регистрируем автолоадер
$loader = new \GIG\Core\Autoloader();
$loader->register();

// Подключение пространств имён
$loader->addNamespace(APPLICATION . '\\Core', PATH_CORE);
$loader->addNamespace(APPLICATION . '\\Domain', PATH_DOMAIN);
$loader->addNamespace(APPLICATION . '\\Domain\\Entities', PATH_ENTITIES);
$loader->addNamespace(APPLICATION . '\\Infrastructure', PATH_INFRASTRUCTURE);
$loader->addNamespace(APPLICATION . '\\Infrastructure\\Repository', PATH_REPOSITORY);
$loader->addNamespace(APPLICATION . '\\Infrastructure\\Persistence', PATH_PERSISTENCE);
$loader->addNamespace(APPLICATION . '\\Infrastructure\\Clients', PATH_CLIENTS);
$loader->addNamespace(APPLICATION . '\\Presentation', PATH_PRESENTATION);
$loader->addNamespace(APPLICATION . '\\Presentation\Controller', PATH_CONTROLLERS);
$loader->addNamespace(APPLICATION . '\\API', PATH_API);
$loader->addNamespace(APPLICATION . '\\Nt', PATH_ROOT . 'Nt' . DS);
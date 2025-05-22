<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Core\Event;
use GIG\Core\Request;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Presentation\Controller\MessageController;

class ErrorHandler
{
    protected static int $eventClass = Event::EVENT_ERROR;

    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        self::$eventClass = $errno === E_USER_NOTICE ? Event::EVENT_INFO : Event::EVENT_WARNING;
        $message = self::composeMessage(
            $errno, 
            $errstr, 
            pathinfo($errfile, PATHINFO_FILENAME), 
            $errline
        );
        self::registerEvent($message);
    }

    public static function handleException(\Throwable $e): void
    {
        self::$eventClass = Event::EVENT_ERROR;

        $extra = $e instanceof GeneralException ? $e->getExtra() : [];
    
        $detail = $extra['detail']
            ?? self::composeMessage($e->getCode(), $e->getMessage(), pathinfo($e->getFile(), PATHINFO_FILENAME), $e->getLine());
    
        self::registerEvent($detail);
    
        self::dispatch($e->getCode(), [
            'title' => Event::getTitle(self::$eventClass),
            'message' => $e->getMessage(),
            'detail' => $detail
        ]);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::$eventClass = Event::EVENT_ERROR;
            $message = self::composeMessage(
                $error['type'],
                $error['message'],
                pathinfo($error['file'], PATHINFO_FILENAME),
                $error['line']
            );
            self::registerEvent($message);
            self::dispatch($error['type'], [
                'title' => Event::getTitle(self::$eventClass),
                'message' => $error['message'],
                'detail' => $message
            ]);
        }
    }

    private static function composeMessage(int $errno, string $errstr, string $errfile, int $errline): string
    {
        return sprintf(
            '%s (%s), модуль %s, строка %s: %s',
            Event::getTitle(self::$eventClass), $errno, $errfile, $errline, $errstr
        );
    }

    private static function registerEvent(string $message): void
    {
        new Event(self::$eventClass, self::class, $message);
    }

    public static function dispatch($code, $payload): void
    {
        if (!headers_sent()) {
            $isApi = Request::isApiStatic();
            $controller = new MessageController();
    
            if ($isApi) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code($code);
                echo json_encode([
                    'status' => 'error',
                    'code' => $code,
                    'message' => $payload['message'] ?? 'Ошибка',
                    'extra' => $payload['extra'] ?? $payload
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                $controller->error($code, $payload);
            }
        }
    }
}

<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Core\Config;
use GIG\Core\Console;

class Event
{
    public const EVENT_INFO = 0;
    public const EVENT_MESSAGE = 1;
    public const EVENT_WARNING = 2;
    public const EVENT_ERROR = 3;
    public const EVENT_FATAL = 4;

    private string $time;
    private int $type;
    private string $source;
    private string $message;

    public static string $logPath = PATH_LOGS . 'events.log';
    private static array $eventTypes = [];

    public function __construct(int $type, string $source, string $message)
    {
        $this->time = date('Y-m-d H:i:s');

        if (empty(self::$eventTypes)) {
            self::$eventTypes = self::getFromConfig();
        }

        $this->type = $type;
        $this->source = self::normalizeSource($source);
        $this->message = $message;

        $this->log();
    }

    private static function normalizeSource(string $fqcn): string
    {
        return basename(str_replace('\\', '/', $fqcn));
    }

    private static function getFromConfig(): array
    {
        return Config::get('events', []);
    }

    public function getText(): string
    {
        return "{$this->source}::[{$this->time}] {$this->message}";
    }

    public static function getTitle(int $class): string
    {
        if (empty(self::$eventTypes)) {
            self::$eventTypes = self::getFromConfig();
        }
        return self::$eventTypes[$class]['title'] ?? 'Unknown';
    }

    public static function getClass(int $class): string
    {
        if (empty(self::$eventTypes)) {
            self::$eventTypes = self::getFromConfig();
        }
        return self::$eventTypes[$class]['class'] ?? 'default';
    }

    private function log(): void
    {
        $logText = $this->getText();
        $logData = [
            'level' => $this->type,
            'class' => self::getClass($this->type),
            'source' => $this->source,
            'time' => $this->time,
            'message' => $this->message
        ];
        Console::setMessage($logData);
        $this->toFile($logText);
        // $this->toDB($logData); // TODO: если понадобится логирование в БД
    }

    private function toFile(string $text): void
    {
        file_put_contents(self::$logPath, $text . PHP_EOL, FILE_APPEND);
    }
}

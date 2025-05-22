<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

class Console
{
    protected static array $messages = [];

    public static function addMessage(int $type, string $source, string $message): bool
    {
        $event = new Event($type, $source, $message);
        return ($event instanceof Event);
    }

    public static function setMessage(array $data): void
    {
        self::$messages = $_SESSION['console_messages'] ?? [];
        self::$messages[] = $data;
        $_SESSION['console_messages'] = self::$messages;
    }

    public static function getMessages(int|string $filter): array
    {
        self::$messages = $_SESSION['console_messages'] ?? [];
        $filterLevel = (int) $filter;
        $filtered = array_filter(self::$messages, function ($msg) use ($filterLevel) {
            return isset($msg['level']) && (int)$msg['level'] >= $filterLevel;
        });
        return array_values($filtered);
    }

    public static function clearMessages(): void
    {
        self::$messages = [];
        unset($_SESSION['console_messages']);
    }
}

<?php
namespace GIG\Infrastructure\Contracts;

defined('_RUNKEY') or die;

interface ServiceClientInterface
{
    public function connect(): bool;
    public function isConnected(): bool;
    public function disconnect(): void;

    /**
     * Выполнить запрос к сервису.
     * @param string $resource
     * @param array $params
     * @return mixed
     */
    public function request(string $resource, array $params = []): mixed;

    /**
     * Вернуть объект соединения, если применимо.
     * @return mixed
     */
    public function getConnection(): mixed;
}

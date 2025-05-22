<?php
namespace GIG\Infrastructure\Clients;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\ServiceClientInterface;
use GIG\Domain\Exceptions\GeneralException;

class LDAPClient implements ServiceClientInterface
{
    protected $connection;
    protected $bind;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    public function connect(): bool
    {
        $conn_string = $this->config['ldap_host'] . ':' . ($this->config['ldap_port'] ?? 389);
        $this->connection = ldap_connect($conn_string);

        if (!$this->connection) {
            throw new GeneralException("Не удалось подключиться к LDAP-серверу", 500, [
                'detail' => "Проверьте конфигурацию: файл init.json, раздел ldap.",
            ]);
        }

        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        $this->bind = ldap_bind(
            $this->connection,
            $this->config['ldap_username'] . '@pnhz.kz',
            $this->config['ldap_password']
        );

        if (!$this->bind) {
            throw new GeneralException("Ошибка привязки к LDAP: неверные учетные данные", 401, [
                'detail' => "Проверьте конфигурацию: файл init.json, раздел ldap.",
            ]);
        }

        return true;
    }

    public function isConnected(): bool
    {
        return !empty($this->connection);
    }

    public function disconnect(): void
    {
        if ($this->connection) {
            ldap_unbind($this->connection);
        }
    }

    public function request(string $resource, array $params = []): array
    {
        // $resource - например фильтр поиска
        // $params - атрибуты поиска
        return $this->search($resource, $params);
    }

    public function getConnection(): mixed
    {
        return $this->connection;
    }

    public function search(string $filter, array $attributes = ['*', '+']): array
    {
        $search = ldap_search($this->connection, $this->config['ldap_dn'], $filter, $attributes);

        if (!$search) {
            return [];
        }
    
        $entries = ldap_get_entries($this->connection, $search);
        $result = [];
    
        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $normalized = $this->normalizeLdapEntry($entry);
                if (!empty($normalized)) {
                    $result[] = $normalized;
                }
            }
        }
    
        return $result;
    }

    public function getUserData(string $samaccountname): array
    {
        if (empty($samaccountname)) {
            return [];
        }

        $filter = "(samaccountname=$samaccountname)";
        $data = $this->search($filter);
        return $data;
    }

    protected function normalizeLdapEntry(array $entry): array
    {
        $normalized = [];
        foreach ($entry as $key => $value) {
            if (is_string($key) && isset($value[0])) {
                $normalized[$key] = $value[0];
            }
        }
        return $normalized;
    }
}

<?php
namespace GIG\Infrastructure\Clients;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\ServiceClientInterface;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Core\Event;
use GIG\Core\Application;

class PercoWebClient implements ServiceClientInterface
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $token = null;
    private bool $authorized = false;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['perco_uri'], '/');
        $this->username = $config['perco_admin'];
        $this->password = $config['perco_password'];

        $this->token = $_SESSION['perco_token'] ?? null;

        if (!$this->token) {
            $this->token = $this->getFromLocalDb();
            if ($this->token) {
                $_SESSION['perco_token'] = $this->token;
            } else {
                $this->connect();
            }
        }
    }

    public function connect(): bool
    {
        $this->getToken();
        return $this->isConnected();
    }

    public function isConnected(): bool
    {
        return !empty($this->token);
    }

    public function disconnect(): void
    {
        $this->token = null;
        unset($_SESSION['perco_token']);
    }

    public function getConnection(): mixed
    {
        return $this->baseUrl;
    }

    public function request(string $resource, array $params = [], array $flags = [], string $method = 'GET'): mixed
    {
        $url = $this->baseUrl . '/' . ltrim($resource, '/');
        $query = '';
        $body = null;
        $headers = [];

        if (!empty($params)) {
            $query = '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        if(!empty($flags)){
            $queryParts = [];
            foreach ($flags as $flag) {
                $queryParts[] = urlencode($flag);
            }
            $query = $query . (!empty($params) ? '&' : '?') . implode('&', $queryParts);
        }

        return $this->sendRequest($method, $url . $query, $body, $headers);
    }

    private function getFromLocalDb(): ?string
    {
        return Application::getInstance()->getDatabase()->value(
            "SELECT value FROM db_settings WHERE param = ?", ['perco_token']
        ) ?: null;
    }

    private function saveToLocalDb(string $token): void
    {
        $db = Application::getInstance()->getDatabase();
        $affected = $db->updateOrInsert('db_settings', ['value' => $token], ['param' => 'perco_token']);
        if ($affected === 0) {
            $db->insert('db_settings', [
                'param' => 'perco_token',
                'value' => $token
            ]);
        }
    }

    private function getToken(): void
    {
        $url = $this->baseUrl . '/system/auth';
        $data = json_encode([
            'login' => $this->username,
            'password' => $this->password
        ]);

        $response = $this->sendRequest('POST', $url, $data, ['Content-Type: application/json'], false);
        $result = json_decode($response, true);

        if (!isset($result['token'])) {
            new Event(Event::EVENT_WARNING, self::class, "Авторизация в PERCo-Web не удалась: $response");
            return;
        }

        $this->token = $result['token'];
        $_SESSION['perco_token'] = $this->token;
        $this->saveToLocalDb($this->token);

        new Event(Event::EVENT_INFO, self::class, 'Токен PERCo-Web успешно получен');
    }

    private function sendRequest(string $method, string $url, ?string $body = null, array $headers = [], bool $useAuth = true): string
    {
        file_put_contents(PATH_LOGS.'perco_request.log', $url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if ($useAuth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Ошибка соединения с сервером PERCo',
                'detail' => curl_error($ch)
            ]);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 401 && $useAuth) {
            $this->disconnect();
            $this->connect();

            if (!$this->token) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'Не удалось обновить токен авторизации PERCo-Web',
                    'detail' => 'Ответ сервера: 401. Повторная авторизация не удалась.'
                ]);
            }

            return $this->sendRequest($method, $url, $body, $headers, $useAuth);
        }

        return $response;
    }

    public function getUserByIdentifier(string $identifier): array
    {
        $query = [
            'card' => $identifier,
            'target' => 'staff'
        ];

        $response = $this->request('users/searchCard', $query);
        $data = json_decode($response, true);

        if (!empty($data['id'])) {
            return ['user_id' => $data['id']];
        }

        return [];
    }

    public function getUserInfoById(int $userId): array
    {
        $response = $this->request('users/staff/' . $userId);
        $data = json_decode($response, true);

        return $data ?? [];
    }

    public function fetchAllUsersFromTable(){
        $allUsers = [];
        $page = 1;
        $rowsPerPage = 100;
        
        do{
            $params = [
                'status' => 'active',
                'page'   => $page,
                'rows'   => $rowsPerPage
            ];
            $response = $this->request('users/staff/table', $params);
            $data = json_decode($response, true);

            if(!is_array($data) || !isset($data['rows'])){
                new Event(Event::EVENT_WARNING, self::class, "Некорректный ответ при получении пользователей PERCo: $response");
                break;
            }
            $allUsers = array_merge($allUsers, $data['rows']);
            $totalPages = (int)($data['total'] ?? 1);
            $page++;
        } while ($page <= $totalPages);
        return $allUsers;
    }

    public function fetchAllUsersFromList(){
        $params = [
            'withCards' => 'true'
        ];
        $response = $this->request('users/staff/list', $params);

        file_put_contents(PATH_LOGS.'perco_debug.log', $response);
        $data = json_decode($response, true);

        return $data ?? [];
    }
}

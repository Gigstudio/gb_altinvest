<?php
namespace GIG\API\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Domain\Services\AuthManager;
use GIG\Infrastructure\Repository\MySQLUserRepository;
use GIG\Core\Application;
use GIG\Domain\Services\LoginGenerator;
use GIG\Domain\Exceptions\GeneralException;

class AuthController extends Controller
{
    protected AuthManager $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthManager();
    }

    public function login(array $data): void
    {
        $login = $data['login'] ?? '';
        $password = $data['pass'] ?? '';

        if (!$login || !$password) {
            throw new GeneralException(
                'Логин и пароль обязательны',
                400,
                [
                    'reason' => 'missing_fields',
                    'detail' => 'Попытка входа без логина или пароля в ' . self::class
                ]
            );
        }

        $result = $this->auth->authenticate($login, $password);
        $this->json($result);
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->json([
            'status' => 'success',
            'message' => 'Вы вышли из системы.'
        ]);
    }

    public function checkLoginSimple(array $data): void
    {
        $login = $data['login'] ?? null;

        if (!$login) {
            throw new GeneralException(
                'Логин не передан.',
                400,
                [
                    'reason' => 'missing_login',
                    // 'short' => 'Логин не передан.',
                    'detail' => 'Попытка проверки логина без параметра login. Контроллер: ' . self::class
                ]
            );
        }

        $repo = new MySQLUserRepository();
        $exists = $repo->findByLogin($login);

        $this->json([
            'status' => $exists ? 'exists' : 'not_found',
            'message' => $exists
                ? 'Логин уже используется в системе.'
                : 'Логин доступен для регистрации.'
        ]);
    }

    public function checkLoginExtended(array $data): void
    {
        $bage = $data['bage'] ?? null;
        $login = $data['login'] ?? null;

        if (!$bage) {
            throw new GeneralException(
                'Номер пропуска не передан.',
                400,
                [
                    'reason' => 'missing_bage',
                    'detail' => 'Параметр bage отсутствует при расширенной проверке логина. Контроллер: ' . self::class
                ]
            );
        }

        if (!$login) {
            throw new GeneralException(
                'Логин не передан.',
                400,
                [
                    'reason' => 'missing_login',
                    'detail' => 'Параметр login отсутствует при расширенной проверке логина. Контроллер: ' . self::class
                ]
            );
        }

        $repo = new MySQLUserRepository();
        $localByBage = $repo->search('bage_number', $bage);

        $local = $repo->findByLogin($login);

        $ldap = null;
        try {
            $ldapClient = Application::getInstance()->getLdap();
            if ($ldapClient) {
                $ldap = $ldapClient->getUserData($login);
            }
        } catch (\Throwable $e) {
            // логгировать при необходимости
        }

        $existsBage = (bool) $localByBage;
        $existsLocal = (bool)$local;
        $existsLdap = !empty($ldap);

        $status = match (true) {
            $existsBage => 'bage_exists',
            $existsLocal && $existsLdap => 'exists_both',
            $existsLocal => 'exists_local',
            $existsLdap => 'exists_ldap',
            default => 'available'
        };

        $this->json([
            'status' => $status,
            'message' => match ($status) {
                'bage_exists' => 'Этот номер пропуска уже зарегистрирован в системе. Обратитесь в службу поддержки пользователей.',
                'exists_both' => 'Такой логин уже используется и в системе, и в AD. Войдите под своей учетной записью или укажите другой логин.',
                'exists_local' => 'Такой логин уже зарегистрирован. Укажите другой или войдите, если это вы.',
                'exists_ldap' => 'Такой логин найден в AD. Войдите под своей учетной записью или укажите другой логин.',
                'available' => 'Логин свободен'
            }
        ]);
    }

    public function register(array $data): void
    {
        $stage = $data['stage'] ?? 'init';
        $repo = new MySQLUserRepository();

        // === Этап 1: генерация логина ===
        if ($stage === 'init') {
            $fio = $data['full_name'] ?? null;

            if (!$fio) {
                throw new GeneralException('Не указано имя для генерации логина.', 400, [
                    'detail' => 'Попытка генерации логина без передачи ФИО в ' . self::class
                ]);
            }

            $login = LoginGenerator::fromFullName($fio);
            $existsLocal = (bool) $repo->findByLogin($login);

            $existsLdap = false;
            try {
                $ldapClient = Application::getInstance()->getLdap();
                if ($ldapClient) {
                    $existsLdap = !empty($ldapClient->getUserData($login));
                }
            } catch (\Throwable) {}

            if ($existsLocal || $existsLdap) {
                $sources = [];
                if ($existsLocal) $sources[] = 'локальной БД';
                if ($existsLdap) $sources[] = 'LDAP';
                $sourceText = implode(' и ', $sources);

                throw new GeneralException('Такой логин уже используется.', 409, [
                    'reason' => 'login_conflict',
                    'detail' => "Логин {$login} уже существует в {$sourceText}."
                ]);
            }

            $this->json([
                'status' => 'ok',
                'login' => $login
            ]);
            return;
        }

        // === Этап 2: сохранение пользователя ===
        $login = trim($data['login'] ?? '');
        $password = $data['password'] ?? '';
        $confirm = $data['confirm'] ?? '';
        $email = trim($data['email'] ?? '');
        $bage = trim($data['bage'] ?? '');
        $foundUser = $data['foundUser'] ?? [];

        if (!$login || !$password || !$confirm || !$email || !$bage) {
            throw new GeneralException('Не все поля заполнены.', 400, [
                'detail' => 'Пропущены поля регистрации: ' . json_encode($data)
            ]);
        }

        if ($password !== $confirm) {
            throw new GeneralException('Пароли не совпадают.', 400, [
                'detail' => 'Регистрация отклонена: несовпадение паролей у логина ' . $login
            ]);
        }

        if ($repo->findByLogin($login)) {
            throw new GeneralException('Такой логин уже зарегистрирован. Выберите другой.', 409, [
                'detail' => 'Повторная попытка регистрации с логином ' . $login . ', уже существующим в БД'
            ]);
        }

        $user = $repo->createFromPerco([
            'login'       => $login,
            'email'       => $email,
            'full_name'   => $foundUser['full_name'] ?? null,
            'first_name'  => $foundUser['first_name'] ?? null,
            'last_name'   => $foundUser['last_name'] ?? null,
            'middle_name' => $foundUser['middle_name'] ?? null,
            'bage_number' => (int)$bage,
            'division_id' => $foundUser['division_id'] ?? null,
            'position_id' => $foundUser['position_id'] ?? null
        ], $password);

        $this->json([
            'status' => 'success',
            'message' => 'Пользователь зарегистрирован.',
            'user' => $user->toArray(['login', 'email', 'full_name'])
        ]);
    }
}
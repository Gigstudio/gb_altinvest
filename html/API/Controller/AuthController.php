<?php
namespace GIG\API\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Domain\Services\AuthManager;
use GIG\Infrastructure\Repository\MySQLUserRepository;
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

            if ($existsLocal) {
                $sources = [];
                if ($existsLocal) $sources[] = 'локальной БД';
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
        $foundUser = $data['foundUser'] ?? [];

        if (!$login || !$password || !$confirm || !$email) {
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
        ], $password);

        $this->json([
            'status' => 'success',
            'message' => 'Пользователь зарегистрирован.',
            'user' => $user->toArray(['login', 'email', 'full_name'])
        ]);
    }
}
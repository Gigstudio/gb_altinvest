<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\Event;
use GIG\Core\PasswordValidator;
use GIG\Infrastructure\Repository\MySQLUserRepository;
use GIG\Domain\Entities\User;

class AuthManager
{
    protected MySQLUserRepository $users;

    public function __construct()
    {
        $this->users = new MySQLUserRepository();
    }

    public function authenticate(string $login, string $password): array
    {
        $app = Application::getInstance();
        $user = $this->users->findByLogin($login);

        if ($user && PasswordValidator::verify($password, $user->password)) {
            $app->setCurrentUser($user);
            $_SESSION['user'] = $user->toArray();
            new Event(Event::EVENT_INFO, self::class, 'Успешная аутентификация (локально)');
            return ['status' => 'success', 'message' => "{$user->getFullName()}! Добро пожаловать!", 'user' => $user->toArray(['login', 'email', 'full_name'])];
        }

        return ['status' => 'error', 'reason' => 'not_found', 'message' => 'Пользователь не найден.'];
    }

    public function logout(): void
    {
        Application::getInstance()->setCurrentUser(null);
        session_destroy();
    }

    public function getCurrentUser(): ?User
    {
        return Application::getInstance()->getCurrentUser();
    }
}

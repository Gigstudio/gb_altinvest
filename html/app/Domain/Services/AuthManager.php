<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\Event;
use GIG\Core\PasswordValidator;
use GIG\Infrastructure\Repository\MySQLUserRepository;
use GIG\Infrastructure\Repository\PercoUserRepository;
use GIG\Infrastructure\Clients\LDAPClient;
use GIG\Domain\Entities\User;

class AuthManager
{
    protected MySQLUserRepository $users;
    protected ?LDAPClient $ldap = null;

    public function __construct()
    {
        $this->users = new MySQLUserRepository();
    }

    private function getLdap(array $config): ?LDAPClient
    {
        try {
            return new LDAPClient($config);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function authenticate(string $login, string $password): array
    {
        $app = Application::getInstance();
        $this->ldap = $this->getLdap($app->getConfig('ldap'));
        $user = $this->users->findByLogin($login);

        if ($user && PasswordValidator::verify($password, $user->password)) {
            $app->setCurrentUser($user);
            $_SESSION['user'] = $user->toArray();
            new Event(Event::EVENT_INFO, self::class, 'Успешная аутентификация (локально)');
            return ['status' => 'success', 'message' => "{$user->getFullName()}! Добро пожаловать!", 'user' => $user->toArray(['login', 'email', 'full_name'])];
        }

        if (!$this->ldap) {
            return ['status' => 'error', 'reason' => 'ldap_unavailable', 'message' => 'LDAP недоступен.'];
        }

        $data = $this->ldap->getUserData($login);
        if (!empty($data[0])) {
            $dn = $data[0]['dn'][0] ?? null;
            if (!$dn) {
                return ['status' => 'error', 'reason' => 'ldap_dn_missing', 'message' => 'DN отсутствует.'];
            }

            $domain = strtolower(str_replace(['DC=', ','], ['', '.'], $app->getConfig('ldap.ldap_dn')));
            $bind = @ldap_bind($this->ldap->getConnection(), "$login@$domain", $password);

            if (!$bind) {
                return ['status' => 'error', 'reason' => 'invalid_password', 'message' => 'Неверный пароль.'];
            }

            if ($user) {
                $hash = PasswordValidator::hash($password);
                $this->users->updatePassword($user->login, $hash);

                $app->setCurrentUser($user);
                $_SESSION['user'] = $user->toArray();
                new Event(Event::EVENT_INFO, self::class, 'Успешная аутентификация (локальная запись после LDAP)');
                return ['status' => 'success', 'message' => "{$user->getFullName()}! Добро пожаловать!", 'user' => $user->toArray(['login', 'email', 'full_name'])];
            }

            $user = $this->users->createFromLdap($data[0], $password);
            
            // try {
            //     // Обогащение данными из PERCo (perco_users)
            //     $fioLdap = $user->getFullName();
            //     $divisionId = $user->division_id;
            
            //     $percoRepo = new PercoUserRepository();
            //     $matches = $percoRepo->findByFio($fioLdap);
            
            //     $percoMatch = null;
            //     if (count($matches) === 1) {
            //         $percoMatch = $matches[0];
            //     } elseif (count($matches) > 1 && $divisionId) {
            //         foreach ($matches as $candidate) {
            //             if ($candidate->division_id == $divisionId) {
            //                 $percoMatch = $candidate;
            //                 break;
            //             }
            //         }
            //     }
            
            //     if ($percoMatch) {
            //         // Здесь перечислить все нужные поля для дообогащения
            //         $user->bage_number    = $percoMatch->identifier;
            //         $user->division_id    = $percoMatch->division_id;
            //         // Добавляй другие поля, если нужно (например, birth_date -> birthday)
            
            //         // Обновляем пользователя в БД
            //         $this->users->update($user);
            
            //         new Event(Event::EVENT_INFO, self::class, "Пользователь {$user->login} дополнен из PERCo по ФИО '$fioLdap'");
            //     } else {
            //         new Event(Event::EVENT_WARNING, self::class, "Не найдено совпадений для пользователя {$user->login} в PERCo (ФИО '$fioLdap')");
            //     }
            // } catch (\Throwable $e) {
            //     new Event(Event::EVENT_WARNING, self::class, "Ошибка при дополнении из PERCo: " . $e->getMessage());
            // }
            
            $app->setCurrentUser($user);
            $_SESSION['user'] = $user->toArray();
            new Event(Event::EVENT_INFO, self::class, 'Создан пользователь из LDAP');
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

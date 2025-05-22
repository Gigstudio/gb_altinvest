<?php
namespace GIG\Infrastructure\Repository;

use GIG\Core\Application;
use GIG\Core\Event;
use GIG\Domain\Entities\User;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Persistence\MySQLClient;

defined('_RUNKEY') or die;

class MySQLUserRepository
{
    protected MySQLClient $db;
    private array $columns = [];

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
        $this->columns = $this->getColumnNames('users');
    }

    private function getColumnNames(string $table): array
    {
        $result = $this->db->describeTable($table);
        return array_column($result, 'Field');
    }

    public function findByLogin(string $login): ?User
    {
        $row = $this->db->first('users', ['login' => $login]);
        return $row ? User::fromArray($row) : null;
    }

    public function search(string $field, string $value): ?User
    {
        if (!in_array($field, $this->columns, true)) {
            throw new GeneralException(
                "Поле '$field' отсутствует в таблице users.",
                400,
                [
                    'reason' => 'missing_login',
                    'detail' => "Поле '$field' отсутствует в таблице users. Поиск невозможен."
                ]
            );
        }
        $row = $this->db->first('users', [$field => $value]);
        return $row ? User::fromArray($row) : null;
    }

    public function save(User $user): void
    {
        // file_put_contents(PATH_LOGS.'debug_create_user.log', date('c')." save({$user->login})\n", FILE_APPEND);
        $data = $user->toArray([
            'login', 'password', 'email', 'full_name', 'first_name', 'last_name', 'middle_name', 'birthday',
            'id_from_1c', 'bage_number', 'company_id', 'division_id', 'position_id',
            'source', 'is_active', 'is_blocked', 'is_fulfilled'
        ]);
        $this->db->insert('users', $data);
        // file_put_contents(PATH_LOGS.'debug_create_user.log', date('c')." inserted({$user->login})\n", FILE_APPEND);
    }

    public function update(User $user): void
    {
        $data = $user->toArray([
            'email', 'full_name', 'first_name', 'last_name', 'middle_name', 'birthday',
            'id_from_1c', 'bage_number', 'company_id', 'division_id', 'position_id',
            'is_active', 'is_blocked', 'is_fulfilled'
        ]);
        $this->db->update('users', $data, ['login' => $user->login]);
    }

    public function updatePassword(string $login, string $newHash): void
    {
        $this->db->update('users', ['password' => $newHash], ['login' => $login]);
    }

    public function createFromLdap(array $ldap, string $plainPassword): ?User
    {
        $login = strtolower($ldap['samaccountname'] ?? '');
        if ($this->findByLogin($login)) {
            new Event(Event::EVENT_WARNING, self::class, "Конфликт логина при импорте из LDAP: $login");
            $this->db->insert('user_conflicts', [
                'login' => $login,
                'source' => 'ldap',
                'full_name' => $ldap['displayname'] ?? null,
                'serialized_data' => json_encode($ldap, JSON_UNESCAPED_UNICODE),
                'detected_at' => date('Y-m-d H:i:s'),
                'resolved' => 0
            ]);
            return null;
        }

        $middleName = $ldap['middlename'] ?? null;
        if (!$middleName && isset($ldap['cn'])) {
            $parts = explode(' ', $ldap['cn']);
            $middleName = $parts[2] ?? null;
        }

        // ----------- COMPANY -----------
        $companyId = null;
        if (!empty($ldap['company'])) {
            $companyId = $this->resolveDictionaryEntry('companies', $ldap['company']);
        }

        // ----------- DIVISION -----------
        $divisionId = null;
        $divisionName = null;
        if (isset($ldap['distinguishedname'])) {
            preg_match_all('/OU=([^,]+)/u', $ldap['distinguishedname'], $ouMatches);
            if (!empty($ouMatches[1])) {
                $divisionName = trim($ouMatches[1][0]);
                $divisionId = $this->resolveDictionaryEntry('divisions', $divisionName);
            }
        }

        $user = new User([
            'login'         => $login,
            'password'      => password_hash($plainPassword, PASSWORD_BCRYPT),
            'email'         => $ldap['mail'] ?? null,
            'full_name'     => $ldap['displayname'] ?? null,
            'first_name'    => $ldap['givenname'] ?? null,
            'last_name'     => $ldap['sn'] ?? null,
            'middle_name'   => $middleName,
            'id_from_1c'    => $ldap['extensionattribute10'] ?? null,
            'company_id'    => $companyId,
            'division_id'   => $divisionId,
            'source'        => 'ldap',
            'is_active'     => 1,
            'is_blocked'    => 0,
            'is_fulfilled'  => 0
        ]);

        // ENRICH — заполняем остальные поля из perco
        $user = $this->enrichUserWithPerco($user, $divisionName);
        $user->is_fulfilled = (int)(!empty($user->bage_number));

        $this->save($user);
        return $this->findByLogin($login);
    }

    public function createFromPerco(array $perco, string $plainPassword): ?User
    {
        $login = strtolower($perco['login'] ?? '');
        if ($this->findByLogin($login)) {
            new Event(Event::EVENT_WARNING, self::class, "Конфликт логина при импорте из PERCo: $login");
            $this->db->insert('user_conflicts', [
                'login' => $login,
                'source' => 'perco',
                'raw_data' => json_encode($perco, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return null;
        }

        $user = new User([
            'login'         => $login,
            'password'      => password_hash($plainPassword, PASSWORD_BCRYPT),
            'email'         => $perco['email'] ?? null,
            'full_name'     => $perco['full_name'] ?? null,
            'first_name'    => $perco['first_name'] ?? null,
            'last_name'     => $perco['last_name'] ?? null,
            'middle_name'   => $perco['middle_name'] ?? null,
            'bage_number'   => $perco['bage_number'] ?? null,
            'division_id'   => $perco['division_id'] ?? null,
            'position_id'   => $perco['position_id'] ?? null,
            'source'        => 'perco',
            'is_active'     => 1,
            'is_blocked'    => 0,
            'is_fulfilled'  => 0
        ]);

        $this->save($user);
        return $this->findByLogin($login);
    }

    /**
     * ENRICH из perco_users и PERCo-Web detailed (поиск по ФИО и подразделению)
     */
    private function enrichUserWithPerco(User $user, ?string $divisionName): User
    {
        try{
            new Event(Event::EVENT_INFO, self::class, "Старт процедуры поиска данных в PERCo для пользователя " . $user->full_name);
            $fio = trim(
                implode(' ', array_filter([
                    $user->last_name,
                    $user->first_name,
                    $user->middle_name
                ]))
            );
            if (!$fio) {
                new Event(Event::EVENT_WARNING, self::class, "enrichUserWithPerco: пустой ФИО!" . json_encode($user));
            }
            return $user;
        } catch (\Throwable $e){
            new Event(Event::EVENT_ERROR, self::class, "Ошибка в enrichUserWithPerco: " . $e->getMessage());
            return $user;
        }
    }

    /**
     * Извлекает id (если есть) и чистое наименование из строки
     * "16085000  оператор товарный" => [16085000, "оператор товарный"]
     */
    private function extractIdAndName(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/^(\d+)[\s-]+(.+)$/u', $raw, $m)) {
            $id = (int)$m[1];
            $name = trim($m[2]);
            return [$id, $name];
        }
        return [null, $raw];
    }

    /**
     * Универсальный метод для работы с справочниками (divisions, positions, companies)
     * Если есть id — ищет/создаёт по id, иначе по name
     */
    private function resolveDictionaryEntry(string $table, string $rawString, ?int $parentId = null): int
    {
        [$id, $name] = $this->extractIdAndName($rawString);
    
        if ($id) {
            $exists = $this->db->value("SELECT id FROM $table WHERE id = ?", [$id]);
            if (!$exists) {
                $fields = ['id' => $id, 'name' => $name];
                if ($table === 'divisions' && $parentId !== null) {
                    $fields['parent_id'] = $parentId;
                }
                $this->db->insert($table, $fields);
                new Event(Event::EVENT_INFO, self::class, "Добавлен $table: $id $name (parent: $parentId)");
            }
            return $id;
        } else {
            $foundId = $this->db->value("SELECT id FROM $table WHERE name = ?", [$name]);
            if ($foundId) return $foundId;
            $fields = ['name' => $name];
            if ($table === 'divisions' && $parentId !== null) {
                $fields['parent_id'] = $parentId;
            }
            $this->db->insert($table, $fields);
            $newId = $this->db->lastInsertId();
            new Event(Event::EVENT_INFO, self::class, "Добавлен $table (auto): $newId $name (parent: $parentId)");
            return $newId;
        }
    }
    
    /**
     * Получить дату рождения из ИИН.
     */
    private function getBirthdateFromIin(string $iin): ?string
    {
        if (preg_match('/^(\d{2})(\d{2})(\d{2})/', $iin, $m)) {
            [$all, $yy, $mm, $dd] = $m;
            $now = (int)date('Y');
            $century = ((int)$yy > (date('y')+1)) ? 1900 : 2000;
            $fullYear = $century + (int)$yy;
            $age = $now - $fullYear;
            if ($age >= 15 && $age <= 85) {
                return sprintf('%04d-%02d-%02d', $fullYear, $mm, $dd);
            }
        }
        return null;
    }

    public function enrichAllPendingUsers(): void
    {
        $pendingUsers = $this->db->get('users', ['is_fulfilled' => 0]);
        foreach ($pendingUsers as $u) {
            $user = User::fromArray($u);
            $oldUser = clone $user;
            $user = $this->enrichUserWithPerco($user, null); // или передавай нужный divisionName
            if (!empty($user->bage_number) && !empty($user->birthday)) {
                $user->is_fulfilled = 1;
                $this->update($user);
                new Event(Event::EVENT_INFO, self::class, "Данные пользователя {$user->login} успешно дополнены из PERCo-Web после синхронизации справочника пользователей");
            } else {
                new Event(Event::EVENT_WARNING, self::class, "Не удалось дополнить данные пользователя {$user->login} из PERCo-Web");
            }
        }
    }

    public function toArray(User $user, array $only = []): array
    {
        return $user->toArray($only);
    }
}

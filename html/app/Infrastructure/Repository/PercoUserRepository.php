<?php
namespace GIG\Infrastructure\Repository;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Persistence\MySQLClient;
use GIG\Core\Application;
use GIG\Core\Event;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Entities\PercoUser;

class PercoUserRepository
{
    protected MySQLClient $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Вставка или обновление по identifier (универсально для любого формата входа)
     */
    public function insertOrUpdate($data): void
    {
        // Определяем тип данных и нормализуем в PercoUser
        if ($data instanceof PercoUser) {
            $percoUser = $data;
        } elseif (isset($data['name']) && isset($data['cards'])) {
            $percoUser = PercoUser::fromApiArray($data);
        } elseif (isset($data['last_name']) && isset($data['identifier'])) {
            $percoUser = PercoUser::fromDetailedArray($data);
        } else {
            throw new GeneralException('Неподдерживаемый формат данных для PercoUser', 500, [
                'detail' => print_r($data, true)
            ]);
        }

        $identifier = $percoUser->identifier;
        if (!$identifier) {
            new Event(Event::EVENT_WARNING, self::class, "Попытка вставки записи perco_users без identifier (номер карты)");
            return;
        }

        $exists = $this->db->value("SELECT id FROM perco_users WHERE identifier = ?", [$identifier]);

        if ($exists) {
            $this->db->update('perco_users', $percoUser->toArray(), ['identifier' => $identifier]);
        } else {
            $this->db->insert('perco_users', $percoUser->toArray());
        }
    }

    /**
     * Найти пользователя по identifier
     * @return PercoUser|null
     */
    public function findByIdentifier(string $identifier): ?PercoUser
    {
        $row = $this->db->first('perco_users', ['identifier' => $identifier]);
        return $row ? PercoUser::fromArray($row) : null;
    }

    /**
     * Найти пользователей по ФИО (строгое сравнение по fio)
     * @return PercoUser[]
     */
    public function findByFio(string $fio): array
    {
        $hasData = $this->db->value("SELECT COUNT(*) FROM perco_users") > 0;
        new Event(Event::EVENT_WARNING, self::class, "findByFio: Записей в perco_users - " . $this->db->value("SELECT COUNT(*) FROM perco_users"));
        $updating = $this->isPercoSyncInProgress();

        if (!$hasData && !$updating) {
            $this->startPercoSyncAsync();
            new Event(Event::EVENT_INFO, self::class, 'Стартована асинхронная синхронизация perco_users');
        }

        $fioNorm = mb_strtoupper(preg_replace('/\s+/', ' ', trim($fio)));
        $result = [];
        foreach ($this->db->get('perco_users') as $row) {
            if (mb_strtoupper(trim($row['fio'])) === $fioNorm) {
                $result[] = PercoUser::fromArray($row);
            }
        }
    
        if (empty($result)) {
            new Event(Event::EVENT_WARNING, self::class, "Совпадений по ФИО в perco_users не найдено: '{$fioNorm}'");
        } else {
            new Event(Event::EVENT_INFO, self::class, "Поиск по ФИО в perco_users: '{$fioNorm}', найдено: " . count($result));
        }
    
        return $result;
    }

    public function startPercoSyncAsync(): void
    {
        // Маркер — чтобы не запускать повторно, если уже идет!
        $this->db->updateOrInsert('db_settings', ['value' => 'in_progress'], ['param' => 'perco_sync']);
        // Запуск через shell_exec — подстрой путь к php и к своему скрипту!
        $cmd = 'php ' . escapeshellarg(PATH_ROOT . 'scripts/refresh_perco_users.php') . ' > '.PATH_LOGS.'sync.log 2>&1 &';
        // $cmd = 'php ' . escapeshellarg(PATH_ROOT . 'scripts/refresh_perco_users.php') . ' > /dev/null 2>&1 &';
        shell_exec($cmd);
    }

    public function isPercoSyncInProgress(): bool
    {
        $state = $this->db->value("SELECT value FROM db_settings WHERE param = 'perco_sync'");
        return $state === 'in_progress';
    }

    public function setPercoSyncDone(): void
    {
        $this->db->updateOrInsert('db_settings', ['value' => 'done'], ['param' => 'perco_sync']);
        $cmd = 'php ' . escapeshellarg(PATH_ROOT . '/scripts/enrich_pending_users.php') . ' > /dev/null 2>&1 &';
        shell_exec($cmd);
    }


    public function findByFioOld(string $fio): array
    {
        $fioNorm = mb_strtoupper(preg_replace('/\s+/', ' ', trim($fio)));
        $result = [];
        $rows = $this->db->get('perco_users');
        foreach ($rows as $row) {
            if (mb_strtoupper(trim($row['fio'])) === $fioNorm) {
                $result[] = PercoUser::fromArray($row);
            }
        }
        if (empty($result)) {
            new Event(Event::EVENT_WARNING, self::class, "Совпадений по ФИО в perco_users не найдено: '{$fioNorm}'");
        } else {
            new Event(Event::EVENT_INFO, self::class, "Поиск по ФИО в perco_users: '{$fioNorm}', найдено: " . count($result));
        }
        return $result;
    }

    /**
     * Получить всех пользователей
     * @return PercoUser[]
     */
    public function fetchAll(): array
    {
        $all = $this->db->get('perco_users');
        return array_map(fn($row) => PercoUser::fromArray($row), $all);
    }

    /**
     * Обновить весь справочник perco_users (массовый рефреш)
     */
    public function refreshPercoUsers(): void
    {
        $percoClient = Application::getInstance()->getPercoWebClient();
        $data = $percoClient->fetchAllUsersFromList();

        if (!is_array($data)) {
            throw new GeneralException("Получены некорректные данные от PERCo-Web", 502, [
                'detail' => print_r($data, true)
            ]);
        }

        $this->db->truncate('perco_users');
        $cnt = 0;
        foreach ($data as $row) {
            $percoUser = PercoUser::fromApiArray($row);
            $this->insertOrUpdate($percoUser);
            $cnt++;
        }
        $this->setPercoSyncDone();
        new Event(Event::EVENT_INFO, self::class, "Справочник perco_users обновлен. Всего загружено: $cnt");
    }
}

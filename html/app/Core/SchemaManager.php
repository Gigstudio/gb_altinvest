<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Contracts\DatabaseClientInterface;
use GIG\Core\Event;
use GIG\Core\Config;
use GIG\Domain\Exceptions\GeneralException;

class SchemaManager
{
    private DatabaseClientInterface $db;
    private string $schemaPath;
    private array $requiredTables = ['users', 'user_conflicts'];
    private string $version;

    public function __construct(DatabaseClientInterface $db)
    {
        $this->db = $db;
        $this->schemaPath = PATH_CONFIG . 'dbschema' . DS;
        $this->version = Config::get('database.dbversion', 'm00000');
    }

    public function checkAndMigrate(): void
    {
        $this->ensureSettingsTable();
        $this->ensureMigrationsTable();

        $current = $this->getCurrentVersion();
        if ($current !== $this->version) {
            $this->applyMigration();
            $this->updateVersion();
        }
    }

    private function getCurrentVersion(): string
    {
        if (!$this->db->tableExists('db_settings')) {
            return '';
        }
        return $this->db->value("SELECT value FROM db_settings WHERE param = ?", ['dbversion']) ?? '';
    }

    private function updateVersion(): void
    {
        $value = $this->db->value("SELECT value FROM db_settings WHERE param = ?", ['dbversion']);
        if ($value === false) {
            trigger_error('Первый запуск!', E_USER_WARNING);
            $this->db->insert('db_settings', [
                'param' => 'dbversion',
                'value' => $this->version
            ]);
        } elseif ($value !== $this->version) {
            trigger_error("Версия базы данных обновлена с {$value} до {$this->version}", E_USER_WARNING);
            $this->db->updateOrInsert('db_settings', ['value' => $this->version], ['param' => 'dbversion']);
        }
    }

    private function ensureSettingsTable(): void
    {
        if (!$this->db->tableExists('db_settings')) {
            $this->db->createTable('db_settings', [
                ['Field' => 'param', 'Type' => 'varchar(64)', 'Null' => 'NO', 'Default' => null, 'Extra' => '', 'Key' => 'PRI'],
                ['Field' => 'value', 'Type' => 'varchar(255)', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => '']
            ]);
            new Event(Event::EVENT_INFO, self::class, "Создана таблица db_settings");
        }
    }

    private function ensureMigrationsTable(): void
    {
        if (!$this->db->tableExists('db_migrations')) {
            $this->db->createTable('db_migrations', [
                ['Field' => 'id', 'Type' => 'int(11)', 'Null' => 'NO', 'Default' => null, 'Extra' => 'auto_increment', 'Key' => 'PRI'],
                ['Field' => 'version', 'Type' => 'varchar(32)', 'Null' => 'NO', 'Default' => null, 'Extra' => '', 'Key' => ''],
                ['Field' => 'applied_at', 'Type' => 'timestamp', 'Null' => 'NO', 'Default' => 'CURRENT_TIMESTAMP', 'Extra' => '', 'Key' => ''],
                ['Field' => 'description', 'Type' => 'text', 'Null' => 'YES', 'Default' => null, 'Extra' => '', 'Key' => '']
            ]);
            new Event(Event::EVENT_INFO, self::class, "Создана таблица db_migrations");
        }
    }

    public function applyMigration(): void
    {
        foreach (scandir($this->schemaPath) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'json') continue;
    
            $table = pathinfo($file, PATHINFO_FILENAME);
            $schemaFile = $this->schemaPath . $file;
    
            $schema = json_decode(file_get_contents($schemaFile), true);
            if (!is_array($schema)) {
                throw new GeneralException("Ошибка чтения схемы", 500, [
                    'detail' => "Файл $file содержит некорректный JSON."
                ]);
            }
    
            if (!$this->db->tableExists($table)) {
                $this->db->createTable($table, $schema);
                new Event(Event::EVENT_INFO, self::class, "Создана таблица $table");
                continue;
            }
    
            // Сравнение и добавление недостающих полей
            $existing = $this->db->describeTable($table); // должен возвращать массив полей
            $existingFields = array_column($existing, 'Field');
            foreach ($schema as $column) {
                if (!in_array($column['Field'], $existingFields, true)) {
                    $this->db->addColumn($table, $column);
                    new Event(Event::EVENT_INFO, self::class, "Добавлено поле {$column['Field']} в таблицу $table");
                }
            }
        }
    }
}

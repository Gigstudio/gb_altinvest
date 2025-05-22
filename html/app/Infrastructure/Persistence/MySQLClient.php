<?php
namespace GIG\Infrastructure\Persistence;

defined('_RUNKEY') or die;

use PDO;
use GIG\Domain\Exceptions\GeneralException;

class MySQLClient extends Database
{
    public function __construct()
    {
        $this->connect(\GIG\Core\Config::get('database'));
    }

    protected function connect(array $config): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['dbhost'],
            $config['dbport'] ?? 3306,
            $config['dbname']
        );

        try {
            $this->pdo = new PDO($dsn, $config['dbuser'], $config['dbpass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new GeneralException("Ошибка соединения с базой данных MySQL", 500, [
                'detail' => "При инициализации соединения возникла проблема подключения. Проверьте данные для подключения, файл init.json",
            ]);
        }
    }

    public function tableExists(string $table): bool
    {
        $this->validateIdentifier($table);
        $sql = "SHOW TABLES LIKE ?";
        return $this->value($sql, [$table]) !== false;
    }

    public function createTable(string $table, array $schema): bool
    {
        $this->validateIdentifier($table);
        $columns = [];
        $primaryKey = null;

        foreach ($schema as $column) {
            $this->validateIdentifier($column['Field']);
            $line = "`{$column['Field']}` {$column['Type']}";
            if ($column['Null'] === 'NO') {
                $line .= " NOT NULL";
            }
            if ($column['Default'] !== null) {
                $default = in_array(strtoupper($column['Default']), ['CURRENT_TIMESTAMP']) ? $column['Default'] : (is_numeric($column['Default']) ? $column['Default'] : "'{$column['Default']}'");
                $line .= " DEFAULT $default";
            }
            if (!empty($column['Extra'])) {
                $line .= " {$column['Extra']}";
            }
            $columns[] = $line;

            if (isset($column['Key']) && strtoupper($column['Key']) === 'PRI') {
                $primaryKey = $column['Field'];
            }
        }

        if ($primaryKey) {
            $columns[] = "PRIMARY KEY (`$primaryKey`)";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(", ", $columns) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->exec($sql, [], "Создание таблицы $table");
        return true;
    }

    public function describeTable(string $table): array
    {
        $this->validateIdentifier($table);
        return $this->exec("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addColumn(string $table, array $column): void
    {
        $this->validateIdentifier($table);
        $name = $column['Field'];
        $type = $column['Type'];
        $null = ($column['Null'] ?? 'YES') === 'NO' ? 'NOT NULL' : 'NULL';
        
        $default = '';
        if (array_key_exists('Default', $column) && $column['Default'] !== null) {
            $raw = strtoupper((string) $column['Default']);
            if ($raw === 'CURRENT_TIMESTAMP') {
                $default = "DEFAULT CURRENT_TIMESTAMP";
            } elseif (is_numeric($column['Default'])) {
                $default = "DEFAULT {$column['Default']}";
            } else {
                $default = "DEFAULT '" . addslashes($column['Default']) . "'";
            }
        }
    
        $extra = $column['Extra'] ?? '';
        
        $sql = "ALTER TABLE `$table` ADD COLUMN `$name` $type $null $default $extra";
        $this->exec($sql);
    }

    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }
}

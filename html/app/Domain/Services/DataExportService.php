<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

class DataExportService
{
    /**
     * Универсальное сохранение данных в формате JSON
     * @param array $data        — Массив данных (quotes, vacancies, news и т.д.)
     * @param string $symbol     — Имя/ключ тикера или сущности (например, KCEL.KZ)
     * @param string $targetDir  — Каталог сохранения (например, /data/quotes/)
     * @return string|false      — Имя файла или false при ошибке
     */
    public static function saveAsJson(array $data, string $symbol, string $targetDir){
        if (empty($data)) return false;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $filename = rtrim($targetDir, "/") . "/" . $symbol . ".json";
        file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $filename;
    }
}
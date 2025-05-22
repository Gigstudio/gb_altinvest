<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

class LoginGenerator
{
    /**
     * Генерация логина по шаблону: и.фамилия
     */
    public static function fromFullName(string $fio): string
    {
        $parts = explode(' ', mb_strtolower(trim($fio)));

        if (count($parts) < 2) {
            return self::translit($parts[0] ?? 'user');
        }

        $initial = mb_substr($parts[1], 0, 1);
        $last = $parts[0];

        return self::translit("{$initial}.{$last}");
    }

    /**
     * Простая транслитерация с поддержкой кириллицы
     */
    public static function translit(string $text): string
    {
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i',
            'й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t',
            'у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y',
            'ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',' '=>'.','-'=>''
        ];

        return strtr($text, $map);
    }
}

<?php
namespace GIG\Infrastructure\Clients;

defined('_RUNKEY') or die;

use GIG\Domain\Exceptions\GeneralException;
use GIG\Core\Event;

class HhApiClient
{
    protected $apiUrl;
    protected $userAgent;
    protected $host;

    public function __construct(string $apiUrl, string $userAgent, string $host = 'hh.kz') {
        $this->apiUrl = rtrim($apiUrl, '/').'/';
        $this->userAgent = $userAgent;
        $this->host = $host;
    }

    /**
     * Универсальный GET-запрос к HH API
     */
    public function get(string $endpoint, array $params = []): array
    {
        if (!isset($params['host']) && $this->host) {
            $params['host'] = $this->host;
        }

        $url = $this->apiUrl . ltrim($endpoint, '/');
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        // new Event(Event::EVENT_INFO, self::class, $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $errorMsg = curl_error($ch);
            curl_close($ch);
            throw new GeneralException(
                "Ошибка CURL при обращении к HH API",
                502,
                [
                    'reason' => 'curl_error',
                    'detail' => $errorMsg
                ]
            );
        }
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new GeneralException(
                "Ошибка HTTP $httpCode от HH API",
                $httpCode,
                [
                    'reason' => 'http_error',
                    'detail' => $response
                ]
            );
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new GeneralException(
                "Ошибка декодирования JSON от HH API",
                500,
                [
                    'reason' => 'json_decode_error',
                    'detail' => $response
                ]
            );
        }
        return $decoded;
    }

    /**
     * Поиск работодателей по названию (strict по документации)
     * https://api.hh.ru/openapi/redoc#operation/get-employers
     */
    // public function searchEmployers(string $query, int $perPage = 5): array
    // {
    //     try {
    //         return $this->get('employers', [
    //             'text' => $query,
    //             'per_page' => $perPage
    //         ]);
    //     } catch (GeneralException $e) {
    //         // Можно добавить логирование, если нужно
    //         throw $e;
    //     }
    // }

    /**
     * Поиск вакансий по employer_id
     */
    public function searchVacancies(array $params): array
    {
        try {
            return $this->get('vacancies', $params);
        } catch (GeneralException $e) {
            throw $e;
        }
    }

    /**
     * Получить конкретную вакансию по id
     */
    public function getVacancy(int $vacancyId): array
    {
        try {
            return $this->get("vacancies/{$vacancyId}");
        } catch (GeneralException $e) {
            throw $e;
        }
    }

    /**
     * Получить вакансии по символу (тикеру) компании
     */
    // public function getVacanciesBySymbol(string $symbol, string $employerName = '', int $vacancyLimit = 10): array
    // {
    //     // 1. Поиск работодателя по названию
    //     $employers = $this->searchEmployers($employerName ?: $symbol, 1);
    //     if (empty($employers['items'])) {
    //         throw new GeneralException(
    //             "Работодатель не найден для $symbol",
    //             404,
    //             [
    //                 'reason' => 'employer_not_found',
    //                 'detail' => "Не найден работодатель по строке поиска: " . ($employerName ?: $symbol)
    //             ]
    //         );
    //     }
    //     $employer = $employers['items'][0];
    //     $employerId = $employer['id'];

    //     // 2. Получение вакансий по employer_id
    //     try {
    //         $vacancies = $this->searchVacancies([
    //             'employer_id' => $employerId,
    //             'per_page' => $vacancyLimit,
    //             'host' => $this->host
    //         ]);
    //     } catch (GeneralException $e) {
    //         throw new GeneralException(
    //             "Ошибка получения вакансий от hh.ru",
    //             502,
    //             [
    //                 'reason' => 'hh_api_error',
    //                 'detail' => $e->getMessage()
    //             ]
    //         );
    //     }

    //     // Логирование успешного запроса (опционально)
    //     new Event(Event::EVENT_INFO, self::class, "Вакансии для $employerId ($employerName) успешно получены.");

    //     return [
    //         'employer' => $employer,
    //         'vacancies' => $vacancies['items'] ?? [],
    //         'total' => $vacancies['found'] ?? 0,
    //     ];
    // }

    /**
     * Получить вакансии по отрасли
     */
    public function getVacanciesByIndustry(string $industry_id, string $area = '40'): array{ //По умолчанию - Казахстан
        $perPage = 100; // Максимум per_page для hh.ru API
        $page = 0;
        $collected = 0;
        $vacancies = [];

        $topEmployers = [];
        $topCities = [];
        $publishStats = [];
        $salaryStats = [
            'до 200 тыс.' => 0,
            '200-400 тыс.' => 0,
            '400-600 тыс.' => 0,
            '600-1000 тыс.' => 0,
            'от 1 млн.' => 0,
            'не указано' => 0
        ];

        do {
            $params = [
                'industry' => $industry_id,
                'area' => $area,
                'per_page' => $perPage,
                'page' => $page,
            ];
            $result = $this->searchVacancies($params);

            if (!isset($result['items']) || !is_array($result['items'])) {
                break;
            }

            foreach ($result['items'] as $vacancy) {
                $vacancies[] = $vacancy;
                $collected++;

                // Работодатель
                $employer = $vacancy['employer']['name'] ?? '-';
                $topEmployers[$employer] = ($topEmployers[$employer] ?? 0) + 1;

                // Город
                $city = $vacancy['area']['name'] ?? '-';
                $topCities[$city] = ($topCities[$city] ?? 0) + 1;

                // Дата публикации (месяц)
                if (!empty($vacancy['published_at'])) {
                    $month = date('Y-m', strtotime($vacancy['published_at']));
                    $publishStats[$month] = ($publishStats[$month] ?? 0) + 1;
                }

                // Зарплата
                if (!empty($vacancy['salary']['from']) || !empty($vacancy['salary']['to'])) {
                    $salary = max($vacancy['salary']['from'] ?? 0, $vacancy['salary']['to'] ?? 0);
                    if ($salary < 200000) $salaryStats['до 200 тыс.']++;
                    elseif ($salary < 400000) $salaryStats['200-400 тыс.']++;
                    elseif ($salary < 600000) $salaryStats['400-600 тыс.']++;
                    elseif ($salary < 1000000) $salaryStats['600-1000 тыс.']++;
                    else $salaryStats['от 1 млн.']++;
                } else {
                    $salaryStats['не указано']++;
                }
            }
            $page++;
            // Проверка: если на странице вакансий меньше perPage — достигнут конец выдачи
        } while (count($result['items']) === $perPage && $page < 20);

        // Топ-10 по убыванию:
        arsort($topEmployers); $topEmployers = array_slice($topEmployers, 0, 10, true);
        arsort($topCities); $topCities = array_slice($topCities, 0, 10, true);
        ksort($publishStats);            

        return ["overall" =>$vacancies, "topEmployers" => $topEmployers, "topCities" => $topCities, "publishStats" => $publishStats, "salaryStats" => $salaryStats];
    }

    public static function saveAsJson(array $vacancies, string $symbol, string $targetDir = '/data/python/vacancies/')
    {
        if (empty($vacancies)) return false;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $filename = $targetDir . $symbol . '.json';
        file_put_contents($filename, json_encode($vacancies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $filename;
    }
}
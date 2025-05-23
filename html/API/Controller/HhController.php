<?php
namespace GIG\API\Controller;

use GIG\Core\Controller;
use GIG\Infrastructure\Clients\HhApiClient;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Core\Event;

class HhController extends Controller
{
    public function getVacancies(array $data): void
    {
        $symbol = $data['symbol'] ?? 'KCEL.KZ'; // Пример: KCEL.KZ
        $area = $data['area'] ?? '40';

        try {
            $client = $this->app->getHhClient();
            $symbols = $this->app->getConfig('tickers', []);
            if (!isset($symbols[$symbol])) {
                throw new GeneralException(
                    "Неизвестный тикер: $symbol",
                    400,
                    ['reason' => 'invalid_symbol', 'detail' => $symbol]
                );
            }
            $industry_id = $symbols[$symbol]['industry_id'] ?? null;
            if (!$industry_id) {
                throw new GeneralException(
                    "Не задан industry_id для тикера $symbol",
                    400,
                    ['reason' => 'missing_industry_id', 'detail' => $symbol]
                );
            }

            $result = $client->getVacanciesByIndustry($industry_id, $area);
            // Экспортируем вакансии в JSON (аналогично saveAsJson для котировок)
            $file = HhApiClient::saveAsJson($result['overall'], $symbol);

            $this->json([
                'status' => 'success',
                'message' => "Получены вакансии по $symbol (отрасль $industry_id), экспортировано в $file",
                'result' => [
                    'symbol' => $symbol,
                    'vacancies' => $result['overall'],
                    'topEmployers' => $result['topEmployers'],
                    'topCities' => $result['topCities'],
                    'publishStats' => $result['publishStats'],
                    'salaryStats' => $result['salaryStats'],
                    'export_file' => $file
                ]
            ]);
        } catch (\Throwable $e) {
            throw new GeneralException(
                "Ошибка получения вакансий для '$symbol'",
                502,
                [
                    'reason' => 'vacancies_not_found',
                    'detail' => $e->getMessage()
                ]
            );
        }
    }
}

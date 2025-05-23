<?php
namespace GIG\API\Controller;

use GIG\Core\Controller;
use GIG\Infrastructure\Clients\HhApiClient;
use GIG\Domain\Exceptions\GeneralException;

class TradernetController extends Controller
{
    public function getVacancies(array $data): void
    {
        $symbol = $data['symbol'] ?? 'KCEL';

        $service = $this->app->getHhClient();

        // try {
        //     $quotes = $service->getQuotes($symbol, $dateFrom, $dateTo);
        //     $this->json([
        //         'status' => 'success',
        //         'message' => "Получены котировки для $symbol с $dateFrom по $dateTo",
        //         'result' => [
        //             'symbol' => $symbol,
        //             'quotes' => $quotes,
        //         ]
        //     ]);
        // } catch (\Throwable $e) {
            
        //     throw new GeneralException(
        //         "Ошибка получения котировок для '$symbol'",
        //         502,
        //         [
        //             'reason' => 'quotes_not_found',
        //             'detail' => "Tradernet API вернул пустой результат или ошибку для тикера $symbol"
        //         ]
        //     );
        // }
    }
}

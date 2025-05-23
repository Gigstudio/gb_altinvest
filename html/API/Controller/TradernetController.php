<?php
namespace GIG\API\Controller;

use GIG\Core\Controller;
use GIG\Domain\Services\TradernetService;
use GIG\Domain\Exceptions\GeneralException;

class TradernetController extends Controller
{
    public function getQuotes(array $data): void
    {
        $symbol = $data['symbol'] ?? 'KCEL';
        $dateFrom = $data['date_from'] ?? '15.08.2022 00:00';
        $dateTo = $data['date_to'] ?? '16.05.2025 00:00';

        $config = $this->app->getConfig('tradernet');
        $apiKey = $config['public_key'] ?? '';
        $apiSecret = $config['secret_key'] ?? '';

        $service = new TradernetService($apiKey, $apiSecret);

        try {
            $quotes = $service->getQuotes($symbol, $dateFrom, $dateTo);
            $this->json([
                'status' => 'success',
                'message' => "Получены котировки для $symbol с $dateFrom по $dateTo",
                'result' => [
                    'symbol' => $symbol,
                    'quotes' => $quotes,
                ]
            ]);
        } catch (\Throwable $e) {
            throw new GeneralException(
                "Ошибка получения котировок для '$symbol'",
                502,
                [
                    'reason' => 'quotes_not_found',
                    'detail' => "Tradernet API вернул пустой результат или ошибку для тикера $symbol"
                ]
            );
        }
    }
}

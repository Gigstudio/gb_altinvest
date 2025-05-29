<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use Nt\PublicApiClient;
use GIG\Domain\Exceptions\GeneralException;

class TradernetService
{
    protected $client;

    public function __construct($apiKey, $apiSecret, $version = PublicApiClient::V2)
    {
        $this->client = new PublicApiClient($apiKey, $apiSecret, $version);
    }

    public function getQuotes($symbol, $dateFrom, $dateTo)
    {
        $command = 'getHloc';
        $params = [
            'id' => $symbol,
            'count' => -1,
            'timeframe' => 1440,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'intervalMode' => 'ClosedRay'
        ];

        try {
            $result = $this->client->sendRequest($command, $params, 'array');
            if (!is_array($result) || isset($result['error'])) {
                // throw new \Exception($result['error'] ?? 'Unknown error from Tradernet');
                throw new GeneralException(
                    $result['error'],
                    400,
                    [
                        'reason' => 'unknown_error',
                        'detail' => self::class . ': получена неизвестная ошибка от Tradernet'
                    ]
                );
            }
            return $result;
        } catch (\Throwable $e) {
            // Можно логировать ошибку
            throw new GeneralException(
                $result['error'],
                400,
                [
                    'reason' => 'unknown_error',
                    'detail' => self::class . ': получена неизвестная ошибка от Tradernet'
                ]
            );
        }
    }

    public function getNews(string $symbol, int $count = 100){
        $command = 'getNews';
        $params = [
            'ticker' => $symbol,
            'limit' => $count
        ];
        try {
            $result = $this->client->sendRequest($command, $params, 'array');
            if (!is_array($result) || isset($result['error'])) {
                // throw new \Exception($result['error'] ?? 'Unknown error from Tradernet');
                throw new GeneralException(
                    $result['error'],
                    400,
                    [
                        'reason' => 'unknown_error',
                        'detail' => self::class . ': получена неизвестная ошибка от Tradernet'
                    ]
                );
            }
            return $result;
        } catch (\Throwable $e) {
            // Можно логировать ошибку
            throw new GeneralException(
                $result['error'],
                400,
                [
                    'reason' => 'unknown_error',
                    'detail' => self::class . ': получена неизвестная ошибка от Tradernet'
                ]
            );
        }
    }

    public function getNewsOld($symbol, $count = 30)
    {
        $apiUrl = 'https://trade.almaty-ffin.kz/api/';
        $params = [
            'cmd' => 'getNews',
            'params' => [
                'ticker' => $symbol,
                'limit' => $count
            ]
        ];
        $q = json_encode($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?q=' . urlencode($q));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if ($result === false) {
            throw new \Exception("Curl error: " . curl_error($ch));
        }
        curl_close($ch);

        $data = json_decode($result, true);
        if (!is_array($data) || isset($data['error'])) {
            throw new \Exception($data['error'] ?? 'Unknown error from Tradernet News');
        }
        return $data['stories'] ?? $data; // API возвращает 'stories' => [...], либо массив
    }

    public static function saveAsCsv(array $quotes, string $symbol, string $targetDir = '/data/quotes/')
    {
        if (empty($quotes)) return false;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $filename = $targetDir . $symbol . '.csv';
        $fp = fopen($filename, 'w');
        fputcsv($fp, ['date', 'open', 'high', 'low', 'close', 'volume']);
        foreach ($quotes as $row) {
            fputcsv($fp, [
                $row['date'] ?? '',
                $row['open'] ?? '',
                $row['high'] ?? '',
                $row['low'] ?? '',
                $row['close'] ?? '',
                $row['volume'] ?? ''
            ]);
        }
        fclose($fp);
        return $filename;
    }

    // public static function saveAsJson(array $data, string $symbol, string $targetDir = '/data/undefined/')
    // {
    //     if (empty($data)) return false;
    //     if (!is_dir($targetDir)) {
    //         mkdir($targetDir, 0777, true);
    //     }
    //     $filename = $targetDir . $symbol . '.json';
    //     file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    //     return $filename;
    // }
}

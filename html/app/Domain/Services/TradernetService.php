<?php
namespace GIG\Domain\Services;

use Nt\PublicApiClient;

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
                throw new \Exception($result['error'] ?? 'Unknown error from Tradernet');
            }
            return $result;
        } catch (\Throwable $e) {
            // Можно логировать ошибку
            throw $e;
        }
    }

    public static function saveAsCsv(array $quotes, string $symbol, string $targetDir = '/data/python/quotes/')
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

    public static function saveAsJson(array $quotes, string $symbol, string $targetDir = '/data/python/quotes/')
    {
        if (empty($quotes)) return false;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $filename = $targetDir . $symbol . '.json';
        file_put_contents($filename, json_encode($quotes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $filename;
    }
}

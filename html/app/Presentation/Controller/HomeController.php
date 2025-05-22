<?php
namespace GIG\Presentation\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Core\AssetManager;
use GIG\Core\Event;
use GIG\Domain\Exceptions\GeneralException;
use Nt\PublicApiClient;

class HomeController extends Controller
{
    public function index(array $data): void
    {
        $pageBlock = $this->buildPage($data);
        $this->render($pageBlock);
    }

    public function testTradernetApi($data)
    {
        AssetManager::addStyle("/assets/css/content.css");
        $data['title'] = 'Котировки';
        $data['symbols'] = $this->app->getConfig('tickers', []);

        $contentPath = __FUNCTION__ . '/content';
        
        $symbol = $data['query']['symbol'] ?? array_key_first($data['symbols']);
        if (!isset($data['symbols'][$symbol])) {
            new Event(Event::EVENT_WARNING, self::class, "Попытка выбрать несуществующий тикер '$symbol', установлен тикер по умолчанию.");
            $data['error'] = "Некорректный тикер ($symbol)! Использован тикер по умолчанию.";
            $symbol = array_key_first($data['symbols']);
        }

        $dateFrom = $data['query']['date_from'] ?? '15.08.2022 00:00';
        $dateTo   = $data['query']['date_to'] ?? '16.05.2025 00:00';

        $quotes = [];
        try {
            $tradernetService = $this->app->getTradernetService();
            $result = $tradernetService->getQuotes($symbol, $dateFrom, $dateTo);

            // Преобразование данных Tradernet к плоскому массиву
            $hloc   = $result['hloc'][$symbol] ?? [];
            $vols   = $result['vl'][$symbol] ?? [];
            $dates  = $result['xSeries'][$symbol] ?? [];

            for ($i = 0; $i < count($hloc); $i++) {
                $quotes[] = [
                    'date'   => isset($dates[$i]) ? date('d.m.Y', $dates[$i]) : '-',
                    'open'   => $hloc[$i][2] ?? '-',
                    'high'   => $hloc[$i][0] ?? '-',
                    'low'    => $hloc[$i][1] ?? '-',
                    'close'  => $hloc[$i][3] ?? '-',
                    'volume' => $vols[$i] ?? '-',
                ];
            }

            // Опционально: название тикера из info
            if (!empty($result['info'][$symbol]['short_name'])) {
                $data['title'] .= ' — ' . htmlspecialchars($result['info'][$symbol]['short_name']);
            }

        } catch (\Throwable $e) {
            new Event(Event::EVENT_ERROR, self::class, "Ошибка получения котировок для '$symbol': " . $e->getMessage());
            $data['error'] = "Данные по тикеру '$symbol' временно недоступны.";
            $quotes = [];
        }

        $data['symbol'] = $symbol;
        $data['quotes'] = $quotes;

        if($tradernetService){
            // $csvFile = $tradernetService::saveAsCsv($quotes, $symbol);
            $jsonFile = $tradernetService::saveAsJson($quotes, $symbol);
        }
        new Event(Event::EVENT_INFO, self::class, "Котировки по $symbol сохранены в $jsonFile для анализа.");

        $pageBlock = $this->buildPage($data, $contentPath, 'CRM-панель');
        $this->render($pageBlock);
    }
}

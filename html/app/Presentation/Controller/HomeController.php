<?php
namespace GIG\Presentation\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Core\Block;
use Nt\PublicApiClient;

require_once PATH_ROOT . 'Nt/PublicApiClient.php';

class HomeController extends Controller
{
    public function index(array $data): void
    {
        $ldap = $this->app->getLdap();
        $perco = $this->app->getPercoWebClient();

        if ($ldap) {
            // console_log('LDAP клиент создан');
            $connection = $ldap->getConnection();
            if ($connection) {
                // console_log('LDAP соединение активно');
                $userInfo = $ldap->getUserData('g.chirikov');
                // $userInfo = $ldap->getUserData('a.abdilmanov');
                // console_log($userInfo, 'Результат getUserData()');
                $data['userinfo'] = $userInfo;
            } else {
                // console_log('Нет соединения с LDAP');
                $data['userinfo'] = null;
            }
        } else {
            // console_log('LDAP клиент недоступен');
            $data['userinfo'] = null;
        }
        
        // $data['userinfo'] = $ldap ? $ldap->getUserData('ai.kadyrbekov') : null;
        $data['percouser'] = $perco ? $perco->getUserInfoById(5600830) : null;
        // $data['percouser'] = $perco ? $perco->getUserInfoById(51665) : null;
        // $data['percousers'] = $perco ? $perco->fetchAllUsersFromList() : null;

        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make('content', $data);
        $bottommenu = Block::make('partials/bottommenu', ['user' => 'Admin']);

        $page = Block::make('layouts/default', ['title' => 'CRM-панель'])
            ->with([
                'head' => $head,
                'mainmenu' => $mainmenu,
                'content' => $content,
                'bottommenu' => $bottommenu,
            ]);

        $this->render($page);
    }

    public function testTradernetApi()
    {
        $config = $this->app->getConfig('tradernet');
        // var_dump($config);
        $apiKey = $config['public_key'];
        $apiSecretKey = $config['secret_key'];
        $version = PublicApiClient::V2;

        $client = new PublicApiClient($apiKey, $apiSecretKey, $version);

        $command = 'getHloc'; 
        $params = [
            "id"           => "KASE",
            "count"        => -1,
            "timeframe"    => 1440,
            "date_from"    => "15.08.2020 00:00",
            "date_to"      => "16.08.2020 00:00",
            "intervalMode" => "ClosedRay"
        ];
        $result = $client->sendRequest($command, $params, 'array');
        print_r($result); // Либо логируй в файл, либо выводи на страницу
    }
}

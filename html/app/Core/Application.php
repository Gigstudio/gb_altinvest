<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Core\Config;
use GIG\Infrastructure\Persistence\MySQLClient;
use GIG\Domain\Services\TradernetService;
use GIG\Core\Request;
use GIG\Core\Response;
use GIG\Core\Router;
use GIG\Domain\Entities\User;

class Application
{
    private Config $config;
    public static Application $app;
    private ?TradernetService $tradernetService = null;
    private ?MySQLClient $db = null;
    private ?User $currentUser = null;

    public Request $request;
    public Response $response;

    private Router $router;

    public function __construct(Config $config)
    {
        self::$app = $this;
        $this->config = $config;

        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request);

        $this->setupAssets();
    }

    public static function getInstance(): self
    {
        return self::$app;
    }

    public static function hasInstance(): bool
    {
        return isset(self::$app);
    }

    public function getConfig(string $key, $default = null): mixed
    {
        return $this->config::get($key, $default);
    }

    public function getTradernetService(): ?TradernetService
    {
        if (!$this->tradernetService) {
            $config = $this->config->get('tradernet');
            $apiKey = $config['public_key'] ?? '';
            $apiSecret = $config['secret_key'] ?? '';
            $version = \Nt\PublicApiClient::V2;
            $this->tradernetService = new TradernetService($apiKey, $apiSecret, $version);
        }
        return $this->tradernetService;
    }
    
    public function getDatabase(): ?MySQLClient
    {
        if (!$this->db) {
            $this->db = new MySQLClient();
        }
        return $this->db;
    }

    public function getCurrentUser(): ?User
    {
        return $this->currentUser;
    }
    
    public function setCurrentUser(?User $user): void
    {
        $this->currentUser = $user;
    }

    private function setupAssets(): void
    {
        $styles = $this->config->get('common.css', []);
        $scripts = $this->config->get('common.js', []);

        foreach ($styles as $css) {
            AssetManager::addStyle("/assets/css/$css.css");
        }
        foreach ($scripts as $js) {
            AssetManager::addScript("/assets/js/$js.js");
        }
    }

    public function run(): void
    {
        try {
            $this->router->loadRoutes();
            $this->router->dispatch();
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }
}

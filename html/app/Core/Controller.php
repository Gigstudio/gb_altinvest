<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Presentation\Controller\MessageController;

abstract class Controller
{
    protected Application $app;
    protected Request $request;
    protected Response $response;
    protected Renderer $renderer;
    public int $statusCode = 200;
    
    public function __construct(){
        $this->app = Application::getInstance();
        $this->request = $this->app->request;
        $this->response = $this->app->response;
        $this->renderer = new Renderer();
    }

    protected function setStatus(int $statusCode){
        $this->statusCode = $statusCode;
    }

    protected function isApiRequest(): bool{
        return $this->request->isApi();
    }

    protected function json(array $payload, int $status = 200): void{
        if (!$this->isApiRequest()) {
            // Не API-запрос — возвращаем HTML-страницу ошибки
            (new MessageController())->error($status, $payload);
            return;
        }

        $payload['status'] ??= $status >= 400 ? 'error' : 'success';
        $payload['code'] ??= $status;
        $this->response->setStatus($status);
        $this->response->json($payload);
    }

    protected function render(Block $block): void{
        if($this->app->getConfig('debug_error.mode', 'deploy') === 'debug'){
            $console = Block::make('partials/console');
            $block = $block->with(['console' => $console]);
        }
        $this->response->html($this->renderer->render($block), $this->statusCode ?? 200);
    }

    protected function buildPage(array $data, string $contentPath = '/content', string $title = 'CRM-панель'): Block
    {
        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make(
            (file_exists(PATH_VIEWS . $contentPath . '.php') ? $contentPath : '/content'),
            $data
        );
        $bottommenu = Block::make('partials/bottommenu', ['user' => 'Admin']);

        return Block::make('layouts/default', ['title' => $title])
            ->with([
                'head' => $head,
                'mainmenu' => $mainmenu,
                'content' => $content,
                'bottommenu' => $bottommenu,
            ]);
    }
}
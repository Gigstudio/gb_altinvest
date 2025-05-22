<?php
namespace GIG\API\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Core\Console;
use GIG\Domain\Exceptions\GeneralException;

class ConsoleController extends Controller
{
    public function get(array $data): void
    {
        $filter = $data['filter'] ?? 0;
        $messages = Console::getMessages((int) $filter);

        $this->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Сообщения получены: всего сообщений - ' . count($messages),
            'messages' => $messages
        ]);
    }

    public function clear(): void
    {
        Console::clearMessages();

        $this->json([
            'status' => 'success',
            'message' => 'Консоль очищена'
        ]);
    }

    public function add(array $data): void
    {
        $level = $data['level'] ?? null;
        $source = $data['source'] ?? null;
        $message = $data['message'] ?? null;

        if (!isset($level, $source, $message)) {
            throw new GeneralException('Недостаточно данных для добавления сообщения', 400, [
                'reason' => 'missing_fields',
                'detail' => 'Не переданы все необходимые поля: ' . json_encode($data)
            ]);
        }

        $result = Console::addMessage((int)$level, $source, $message);

        if ($result === true) {
            $this->json([
                'status' => 'success',
                'message' => 'Сообщение добавлено'
            ]);
        } else {
            throw new GeneralException('Ошибка при добавлении сообщения', 500, [
                'reason' => 'add_failed',
                'detail' => 'Console::addMessage вернул false или null при добавлении сообщения'
            ]);
        }
    }
}

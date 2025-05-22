<?php
namespace GIG\API\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\Controller;
use GIG\Core\Event;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Repository\PercoUserRepository;

class PercoController extends Controller
{
    public function lookup(array $data): void
    {
        $identifier = $data['identifier'] ?? '';

        if (!$identifier) {
            throw new GeneralException(
                'Номер пропуска не передан',
                400,
                [
                    'detail' => 'Параметр identifier отсутствует в запросе /api/perco.php',
                    'reason' => 'missing_identifier'
                ]
            );
        }

        $repo = new PercoUserRepository();
        new Event(Event::EVENT_INFO, self::class, "Получен запрос на поиск пользователя perco_users по номеру пропуска $identifier");

        // 1. Поиск в локальном справочнике
        $user = $repo->findByIdentifier($identifier);

        // 2. Если не найден — пробуем через внешний API
        if (!$user) {
            new Event(Event::EVENT_INFO, self::class, "Пользователь $identifier не найден в локальной базе, обращаемся к PERCo-Web.");
            try {
                $perco = Application::getInstance()->getPercoWebClient();
                $card = $perco->getUserByIdentifier($identifier);

                if (isset($card['user_id'])) {
                    $userData = $perco->getUserInfoById($card['user_id']);
                    if (!empty($userData)) {
                        $repo->insertOrUpdate($userData);
                        $user = $repo->findByIdentifier($identifier);
                    }
                }
            } catch (\Throwable $e) {
                // Ошибка получения из внешнего API
                throw new GeneralException(
                    'Ошибка обращения к PERCo-Web',
                    500,
                    ['detail' => $e->getMessage()]
                );
            }
        }

        if ($user) {
            $this->json([
                'status' => 'success',
                'message' => 'Пользователь найден',
                'data' => $user
            ]);
            return;
        } else {
            new Event(Event::EVENT_WARNING, self::class, "Пользователь с номером пропуска $identifier не найден ни в локальном справочнике, ни через PERCo-Web.");
            throw new GeneralException(
                "Пользователь PERCo-Web по номеру пропуска $identifier не найден",
                404,
                [
                    'detail' => "Пользователь с пропуском $identifier отсутствует в локальной базе и не найден в PERCo-Web.",
                    'reason' => 'not_found'
                ]
            );
        }
    }

    // 2. Ручной refresh — массовое обновление справочника
    public function refresh(): void
    {
        $repo = new PercoUserRepository();
        try {
            $repo->refreshPercoUsers();
            $this->json([
                'status' => 'success',
                'message' => 'Справочник perco_users обновлён'
            ]);
        } catch (\Throwable $e) {
            throw new GeneralException(
                'Ошибка массового обновления справочника perco_users',
                500,
                ['detail' => $e->getMessage()]
            );
        }
    }
}

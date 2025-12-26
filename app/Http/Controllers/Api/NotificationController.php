<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationTool;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationTool;

    public function __construct(NotificationTool $notificationTool)
    {
        $this->notificationTool = $notificationTool;
    }

    /**
     * Получить уведомления
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        $notifications = $this->notificationTool->getNotificationsJson($request->user(), $limit);

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    /**
     * Получить все уведомления с пагинацией и фильтрацией
     */
    public function all(Request $request)
    {
        $filters = [
            'read' => $request->get('read'),
            'type' => $request->get('type'),
            'search' => $request->get('search'),
        ];

        $perPage = $request->get('per_page', 20);
        $notifications = $this->notificationTool->getAllNotifications($request->user(), $filters, $perPage);

        return response()->json($notifications);
    }

    /**
     * Отметить уведомление как прочитанное
     */
    public function markAsRead(Request $request, string $id)
    {
        $this->notificationTool->markAsRead($request->user(), (int) $id);

        return response()->json([
            'message' => 'Уведомление отмечено как прочитанное',
        ]);
    }

    /**
     * Удалить уведомление
     */
    public function destroy(Request $request, string $id)
    {
        $deleted = $this->notificationTool->deleteNotification($request->user(), (int) $id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Уведомление не найдено',
            ], 404);
        }

        return response()->json([
            'message' => 'Уведомление успешно удалено',
        ]);
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function unreadCount(Request $request)
    {
        $count = $this->notificationTool->getUnreadCount($request->user());

        return response()->json([
            'count' => $count,
        ]);
    }
}

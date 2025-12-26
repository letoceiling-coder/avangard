<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationTool
{
    /**
     * Получить уведомления для пользователя
     *
     * @param User|null $user
     * @param int $limit
     * @param bool $onlyUnread
     * @return Collection
     */
    public function getNotifications(?User $user = null, int $limit = 10, bool $onlyUnread = true): Collection
    {
        if (!$user) {
            return collect([]);
        }

        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($onlyUnread) {
            $query->where('read', false);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Добавить уведомление
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param string|null $type
     * @return Notification
     */
    public function addNotification(User $user, string $title, string $message, ?string $type = 'info'): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'read' => false,
        ]);
    }

    /**
     * Отметить уведомление как прочитанное
     *
     * @param User $user
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(User $user, int $notificationId): bool
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Получить количество непрочитанных уведомлений
     *
     * @param User|null $user
     * @return int
     */
    public function getUnreadCount(?User $user = null): int
    {
        if (!$user) {
            return 0;
        }

        return Notification::where('user_id', $user->id)
            ->where('read', false)
            ->count();
    }

    /**
     * Получить все уведомления с пагинацией и фильтрацией
     *
     * @param User|null $user
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllNotifications(?User $user = null, array $filters = [], int $perPage = 20)
    {
        if (!$user) {
            return \Illuminate\Pagination\LengthAwarePaginator::make([], 0, $perPage);
        }

        $query = Notification::where('user_id', $user->id);

        // Фильтр по статусу прочитанности
        if (isset($filters['read'])) {
            $query->where('read', $filters['read']);
        }

        // Фильтр по типу
        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        // Поиск по заголовку и сообщению
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Удалить уведомление
     *
     * @param User $user
     * @param int $notificationId
     * @return bool
     */
    public function deleteNotification(User $user, int $notificationId): bool
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Получить уведомления в формате JSON для API
     *
     * @param User|null $user
     * @param int $limit
     * @return array
     */
    public function getNotificationsJson(?User $user = null, int $limit = 10): array
    {
        return $this->getNotifications($user, $limit)->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'read' => $notification->read,
                'created_at' => $notification->created_at->toDateTimeString(),
                'created_at_human' => $notification->created_at->diffForHumans(),
            ];
        })->toArray();
    }
}

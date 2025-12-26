<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class AdminMenu
{
    /**
     * Получить меню для пользователя с фильтрацией по ролям
     *
     * @param User|null $user
     * @return Collection
     */
    public function getMenu(?User $user = null): Collection
    {
        $menu = collect([
            [
                'title' => 'Медиа',
                'route' => 'admin.media',
                'icon' => 'image',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Уведомления',
                'route' => 'admin.notifications',
                'icon' => 'bell',
                'roles' => ['admin', 'manager', 'user'],
            ],
            [
                'title' => 'Пользователи',
                'route' => 'admin.users',
                'icon' => 'users',
                'roles' => ['admin'],
            ],
            [
                'title' => 'Роли',
                'route' => 'admin.roles',
                'icon' => 'shield',
                'roles' => ['admin'],
            ],
            [
                'title' => 'WOW Рулетка',
                'icon' => 'circle',
                'roles' => ['admin', 'manager'],
                'children' => [
                    [
                        'title' => 'Каналы',
                        'route' => 'admin.wow.channels',
                        'icon' => 'message-circle',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Рулетка',
                        'route' => 'admin.wow.wheel',
                        'icon' => 'circle',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Рассылки',
                        'route' => 'admin.wow.broadcast',
                        'icon' => 'send',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Типы призов',
                        'route' => 'admin.wow.prize-types',
                        'icon' => 'gift',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Пользователи WOW',
                        'route' => 'admin.wow.users',
                        'icon' => 'users',
                        'roles' => ['admin', 'manager'],
                    ],
                    [
                        'title' => 'Рефералы',
                        'route' => 'admin.wow.referrals',
                        'icon' => 'user-plus',
                        'roles' => ['admin', 'manager'],
                    ],
                           [
                               'title' => 'Статистика',
                               'route' => 'admin.wow.statistics',
                               'icon' => 'bar-chart',
                               'roles' => ['admin', 'manager'],
                           ],
                           [
                               'title' => 'Призы лидерборда',
                               'route' => 'admin.wow.leaderboard',
                               'icon' => 'trophy',
                               'roles' => ['admin', 'manager'],
                           ],
                           [
                               'title' => 'Победители',
                               'route' => 'admin.wow.winners',
                               'icon' => 'award',
                               'roles' => ['admin', 'manager'],
                           ],
                           [
                               'title' => 'Приветствие / Баннер',
                               'route' => 'admin.wow.welcome',
                               'icon' => 'message-square',
                               'roles' => ['admin', 'manager'],
                           ],
                ],
            ],
            [
                'title' => 'Боты',
                'route' => 'admin.bots',
                'icon' => 'bot',
                'roles' => ['admin'],
            ],
            [
                'title' => 'Конфигурации',
                'icon' => 'settings',
                'roles' => ['admin'],
                'children' => [
                    [
                        'title' => 'Бот',
                        'route' => 'admin.settings.bot',
                        'icon' => 'bot',
                        'roles' => ['admin'],
                    ],
                    [
                        'title' => 'Telegram Stars',
                        'route' => 'admin.settings.stars',
                        'icon' => 'star',
                        'roles' => ['admin', 'superadmin'],
                    ],
                ],
            ],
            [
                'title' => 'Подписка',
                'route' => 'admin.subscription',
                'icon' => 'credit-card',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Поддержка',
                'route' => 'admin.support',
                'icon' => 'chat',
                'roles' => ['admin', 'manager'],
            ],
            [
                'title' => 'Документация',
                'route' => 'admin.documentation',
                'icon' => 'book',
                'roles' => ['admin', 'manager', 'user'],
            ],
        ]);

        if (!$user) {
            return collect([]);
        }

        // Получаем роли пользователя
        $userRoles = $user->roles->pluck('slug')->toArray();

        // Фильтруем меню по ролям
        return $menu->map(function ($item) use ($userRoles) {
            // Проверяем доступ к родительскому элементу
            if (!empty($item['roles']) && !$this->hasAccess($userRoles, $item['roles'])) {
                return null;
            }

            // Фильтруем дочерние элементы
            if (isset($item['children'])) {
                $item['children'] = collect($item['children'])->filter(function ($child) use ($userRoles) {
                    return empty($child['roles']) || $this->hasAccess($userRoles, $child['roles']);
                })->values()->toArray();

                // Если нет доступных дочерних элементов, скрываем родительский
                if (empty($item['children'])) {
                    return null;
                }
            }

            return $item;
        })->filter()->values();
    }

    /**
     * Проверить доступ пользователя к элементу меню
     *
     * @param array $userRoles
     * @param array $requiredRoles
     * @return bool
     */
    protected function hasAccess(array $userRoles, array $requiredRoles): bool
    {
        return !empty(array_intersect($userRoles, $requiredRoles));
    }

    /**
     * Получить меню в формате JSON для API
     *
     * @param User|null $user
     * @return array
     */
    public function getMenuJson(?User $user = null): array
    {
        return $this->getMenu($user)->toArray();
    }
}

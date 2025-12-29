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
                'title' => 'Боты',
                'route' => 'admin.bots',
                'icon' => 'bot',
                'roles' => ['admin'],
            ],
            [
                'title' => 'Парсер Trend SSO',
                'route' => 'admin.trendsso-parser',
                'icon' => 'link',
                'roles' => ['admin'],
            ], // Trend SSO Parser menu item
            [
                'title' => 'Регионы',
                'route' => 'admin.regions',
                'icon' => 'map-pin',
                'roles' => ['admin'],
            ],
            [
                'title' => 'Парсер данных',
                'route' => 'admin.parser.dashboard',
                'icon' => 'database',
                'roles' => ['admin'],
            ],
            [
                'title' => 'Настройки',
                'route' => 'admin.settings',
                'icon' => 'settings',
                'roles' => ['admin'],
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
                'title' => 'Логи',
                'route' => 'admin.logs',
                'icon' => 'file-text',
                'roles' => ['admin'],
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

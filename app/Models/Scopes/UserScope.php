<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class UserScope implements Scope
{
    /**
     * Кеш для проверки наличия колонки user_id в таблицах
     * 
     * @var array<string, bool>
     */
    private static array $hasUserIdColumnCache = [];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Применяем фильтр только если пользователь авторизован
        // и в таблице есть колонка user_id
        if (auth()->check()) {
            $table = $model->getTable();
            
            // Проверяем наличие колонки user_id (с кешированием)
            if ($this->hasUserIdColumn($table)) {
                // Для папок: не применяем фильтр к корзине (is_trash = 1)
                // Корзина должна быть общей для всех пользователей
                // Также показываем папки с user_id = NULL (системные/общие папки)
                if ($table === 'folders') {
                    $builder->where(function ($query) use ($table) {
                        // Показываем папки, где:
                        // 1. Корзина (is_trash = 1) - для всех пользователей
                        // 2. ИЛИ обычная папка (is_trash != 1 или NULL) И (user_id = текущий пользователь ИЛИ user_id = NULL)
                        $query->where($table . '.is_trash', 1)
                              ->orWhere(function ($q) use ($table) {
                                  // Обычная папка (не корзина)
                                  $q->where(function ($subQ) use ($table) {
                                      $subQ->where($table . '.is_trash', '!=', 1)
                                           ->orWhereNull($table . '.is_trash');
                                  })
                                  // И принадлежит текущему пользователю ИЛИ системная (user_id = NULL)
                                  ->where(function ($subQ) use ($table) {
                                      $subQ->where($table . '.user_id', auth()->id())
                                           ->orWhereNull($table . '.user_id');
                                  });
                              });
                    });
                } else {
                    // Для media файлов: показываем файлы текущего пользователя ИЛИ системные (user_id = NULL)
                    // Это позволяет отображать общие файлы, например иконки колеса
                    if ($table === 'media') {
                        $builder->where(function ($query) use ($table) {
                            $query->where($table . '.user_id', auth()->id())
                                  ->orWhereNull($table . '.user_id');
                        });
                } elseif ($table === 'media') {
                    // Для media файлов: показываем файлы текущего пользователя ИЛИ системные (user_id = NULL)
                    // Это позволяет отображать общие файлы, например иконки колеса
                    $builder->where(function ($query) use ($table) {
                        $query->where($table . '.user_id', auth()->id())
                              ->orWhereNull($table . '.user_id');
                    });
                } else {
                    // Для других таблиц применяем обычный фильтр
                    $builder->where($table . '.user_id', auth()->id());
                }
                }
            }
        }
    }

    /**
     * Проверить наличие колонки user_id в таблице (с кешированием)
     * 
     * @param string $table
     * @return bool
     */
    private function hasUserIdColumn(string $table): bool
    {
        if (!isset(self::$hasUserIdColumnCache[$table])) {
            self::$hasUserIdColumnCache[$table] = Schema::hasColumn($table, 'user_id');
        }
        
        return self::$hasUserIdColumnCache[$table];
    }
}

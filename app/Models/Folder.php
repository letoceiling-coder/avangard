<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use App\Models\Traits\HasUserScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель папки медиа-менеджера
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $src
 * @property int|null $parent_id
 * @property int $position
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Folder|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|Folder[] $children
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $files
 * @property-read int $filesCount
 */
class Folder extends Model
{
    use Filterable, HasUserScope, SoftDeletes;

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'src',
        'parent_id',
        'position',
        'protected',
        'is_trash',
        'user_id',
        'deleted_at',
    ];

    /**
     * Связи, которые всегда загружаются (для оптимизации убрать 'user' и 'files' при необходимости)
     * 
     * @var array<string>
     */
    protected $with = ['user', 'files'];

    /**
     * Дополнительные атрибуты
     * 
     * @var array<string>
     */
    protected $appends = ['filesCount'];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'parent_id' => 'int',
        'position' => 'int',
        'protected' => 'boolean',
        'is_trash' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Родительская папка
     * 
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id', 'id');
    }

    /**
     * Дочерние папки
     * 
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id', 'id')
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Пользователь, создавший папку
     * 
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Файлы в папке
     * 
     * @return HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Количество файлов в папке
     * 
     * @return Attribute
     */
    public function filesCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->files()->count(),
        );
    }

    /**
     * Accessor и mutator для slug
     * Автоматически генерирует slug из названия
     * 
     * @return Attribute
     */
    public function slug(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? str(strtolower($this->name))->slug(),
            set: fn ($value) => $value ?: str(strtolower($this->name))->slug(),
        );
    }

    /**
     * Получить полный путь к папке (включая родительские)
     * 
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    /**
     * Проверить, является ли папка корневой
     * 
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Проверить, есть ли вложенные папки
     * 
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Проверить, есть ли файлы в папке
     * 
     * @return bool
     */
    public function hasFiles(): bool
    {
        return $this->files()->exists();
    }

    /**
     * Проверить, является ли папка защищенной
     * 
     * @return bool
     */
    public function isProtected(): bool
    {
        return $this->protected === true;
    }

    /**
     * Проверить, является ли папка корзиной
     * 
     * @return bool
     */
    public function isTrash(): bool
    {
        return $this->is_trash === true;
    }

    /**
     * Получить папку корзины
     * Корзина общая для всех пользователей, поэтому обходим UserScope
     * 
     * @return Folder|null
     */
    public static function getTrashFolder(): ?Folder
    {
        return self::withoutUserScope()->where('is_trash', true)->first();
    }
}

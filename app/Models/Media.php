<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use App\Models\Traits\HasUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Модель медиа-файла
 * 
 * @property int $id
 * @property string $name
 * @property string $original_name
 * @property string $extension
 * @property string $disk
 * @property int|null $width
 * @property int|null $height
 * @property string $type
 * @property int $size
 * @property int|null $folder_id
 * @property int|null $user_id
 * @property string|null $telegram_file_id
 * @property string|null $metadata
 * @property bool $temporary
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Folder|null $folder
 * @property-read User|null $user
 * @property-read string $url
 * @property-read string|null $fullPath
 * @property-read string $sizeFormatted
 */
class Media extends Model
{
    use Filterable, HasUserScope;

    /**
     * Имя таблицы
     * 
     * @var string
     */
    protected $table = 'media';

    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'original_name',
        'extension',
        'disk',
        'width',
        'height',
        'type',
        'size',
        'folder_id',
        'original_folder_id',
        'user_id',
        'telegram_file_id',
        'metadata',
        'temporary',
        'deleted_at',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'size' => 'integer',
        'folder_id' => 'integer',
        'original_folder_id' => 'integer',
        'user_id' => 'integer',
        'temporary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Связь с папкой
     * 
     * @return BelongsTo
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id', 'id');
    }

    /**
     * Связь с пользователем
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    /**
     * Получить полный URL файла
     * 
     * @return string
     */
    public function getUrlAttribute(): string
    {
        $metadata = $this->metadata ? json_decode($this->metadata, true) : [];
        $path = $metadata['path'] ?? ($this->disk . '/' . $this->name);
        
        return '/' . ltrim($path, '/');
    }

    /**
     * Получить полный путь к файлу на сервере
     * 
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        $metadata = $this->metadata ? json_decode($this->metadata, true) : [];
        $path = $metadata['path'] ?? ($this->disk . '/' . $this->name);
        
        return public_path($path);
    }

    /**
     * Получить размер файла в читаемом формате
     * 
     * @return string
     */
    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Проверить, является ли файл изображением
     * 
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->type === 'photo';
    }

    /**
     * Проверить, является ли файл видео
     * 
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Проверить, является ли файл документом
     * 
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    /**
     * Проверить, существует ли файл физически
     * 
     * @return bool
     */
    public function fileExists(): bool
    {
        return file_exists($this->fullPath);
    }

    /**
     * Получить MIME тип файла
     * 
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        $metadata = $this->metadata ? json_decode($this->metadata, true) : [];
        return $metadata['mime_type'] ?? null;
    }

    /**
     * Scope для фильтрации по типу файла
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope для фильтрации по папке
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $folderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInFolder($query, ?int $folderId)
    {
        if ($folderId === null) {
            return $query->whereNull('folder_id');
        }
        
        return $query->where('folder_id', $folderId);
    }

    /**
     * Scope для фильтрации временных файлов
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $temporary
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTemporary($query, bool $temporary = true)
    {
        return $query->where('temporary', $temporary);
    }
}

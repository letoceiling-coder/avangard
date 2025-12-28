<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель расписания парсера
 */
class ParserSchedule extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'object_type',
        'city_ids',
        'time_from',
        'time_to',
        'days_of_week',
        'is_active',
        'check_images',
        'force_update',
        'limit',
        'offset',
        'skip_errors',
        'last_run_at',
        'next_run_at',
        'last_run_total',
        'last_run_created',
        'last_run_updated',
        'last_run_errors',
        'description',
        'metadata',
    ];
    
    protected $casts = [
        'city_ids' => 'array',
        'days_of_week' => 'array',
        'is_active' => 'boolean',
        'check_images' => 'boolean',
        'force_update' => 'boolean',
        'skip_errors' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'limit' => 'integer',
        'offset' => 'integer',
        'last_run_total' => 'integer',
        'last_run_created' => 'integer',
        'last_run_updated' => 'integer',
        'last_run_errors' => 'integer',
        'metadata' => 'array',
    ];
    
    /**
     * Типы объектов
     */
    public const OBJECT_TYPES = [
        'blocks' => 'Блоки (Квартиры)',
        'parkings' => 'Паркинги',
        'villages' => 'Поселки (Дома с участками)',
        'plots' => 'Участки',
        'commercial-blocks' => 'Коммерческие объекты',
        'commercial-premises' => 'Коммерческие помещения',
        'builders' => 'Подрядчики',
    ];
    
    /**
     * Дни недели
     */
    public const DAYS_OF_WEEK = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];
    
    /**
     * Scope: активные расписания
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Проверить, нужно ли запускать расписание сейчас
     */
    public function shouldRunNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        $now = now();
        $currentTime = $now->format('H:i:s');
        $currentDay = $now->dayOfWeekIso; // 1 (понедельник) - 7 (воскресенье)
        
        // Проверка дня недели
        if ($this->days_of_week !== null && !in_array($currentDay, $this->days_of_week)) {
            return false;
        }
        
        // Проверка времени (time_from и time_to хранятся как TIME)
        $timeFrom = $this->time_from instanceof \Carbon\Carbon 
            ? $this->time_from->format('H:i:s') 
            : $this->time_from;
        $timeTo = $this->time_to instanceof \Carbon\Carbon 
            ? $this->time_to->format('H:i:s') 
            : $this->time_to;
        
        if ($timeFrom <= $timeTo) {
            // Обычный случай: время в пределах одного дня
            return $currentTime >= $timeFrom && $currentTime <= $timeTo;
        } else {
            // Пересечение полночи (например, 22:00 - 06:00)
            return $currentTime >= $timeFrom || $currentTime <= $timeTo;
        }
    }
    
    /**
     * Получить название типа объекта
     */
    public function getObjectTypeNameAttribute(): string
    {
        return self::OBJECT_TYPES[$this->object_type] ?? $this->object_type;
    }
    
    /**
     * Получить список дней недели в виде строки
     */
    public function getDaysOfWeekStringAttribute(): string
    {
        if ($this->days_of_week === null) {
            return 'Все дни';
        }
        
        $days = [];
        foreach ($this->days_of_week as $day) {
            $days[] = self::DAYS_OF_WEEK[$day] ?? $day;
        }
        
        return implode(', ', $days);
    }
    
    /**
     * Обновить статистику последнего запуска
     */
    public function updateRunStats(int $total, int $created, int $updated, int $errors): void
    {
        $this->update([
            'last_run_at' => now(),
            'last_run_total' => $total,
            'last_run_created' => $created,
            'last_run_updated' => $updated,
            'last_run_errors' => $errors,
        ]);
    }
}

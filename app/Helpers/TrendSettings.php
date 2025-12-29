<?php

namespace App\Helpers;

use App\Models\Setting;

/**
 * Helper для получения настроек TrendAgent
 */
class TrendSettings
{
    /**
     * Телефон по умолчанию
     */
    private const DEFAULT_PHONE = '+79045393434';

    /**
     * Пароль по умолчанию
     */
    private const DEFAULT_PASSWORD = 'nwBvh4q';

    /**
     * Получить телефон для авторизации
     */
    public static function getPhone(): string
    {
        return Setting::get('trend.phone', self::DEFAULT_PHONE);
    }

    /**
     * Получить пароль для авторизации
     */
    public static function getPassword(): string
    {
        return Setting::get('trend.password', self::DEFAULT_PASSWORD);
    }

    /**
     * Получить оба значения (телефон и пароль)
     */
    public static function getCredentials(): array
    {
        return [
            'phone' => self::getPhone(),
            'password' => self::getPassword(),
        ];
    }
}


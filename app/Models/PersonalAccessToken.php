<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Get the database connection name for the model.
     * Используем базу данных из env, а не из заголовков
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return config('database.default');
    }
}

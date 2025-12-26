<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    protected $fillable = [
        'name',
        'token',
        'username',
        'webhook_url',
        'webhook_registered',
        'welcome_message',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'webhook_registered' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
}

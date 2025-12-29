<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->string('group')->default('general'); // general, trend, parser, etc.
            $table->timestamps();
            
            $table->index(['group', 'key']);
        });

        // Вставляем настройки по умолчанию для TrendAgent
        DB::table('settings')->insert([
            [
                'key' => 'trend.phone',
                'value' => '+79045393434',
                'type' => 'string',
                'description' => 'Телефон для авторизации в TrendAgent API',
                'group' => 'trend',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'trend.password',
                'value' => 'nwBvh4q',
                'type' => 'string',
                'description' => 'Пароль для авторизации в TrendAgent API',
                'group' => 'trend',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

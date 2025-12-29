<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Удаляем старый уникальный индекс на guid
            $table->dropUnique(['guid']);
            
            // Добавляем составной уникальный индекс на city_id + guid
            // Это позволяет иметь одинаковые guid в разных городах
            $table->unique(['city_id', 'guid'], 'locations_city_guid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Восстанавливаем старый уникальный индекс
            $table->dropUnique('locations_city_guid_unique');
            $table->unique('guid');
        });
    }
};


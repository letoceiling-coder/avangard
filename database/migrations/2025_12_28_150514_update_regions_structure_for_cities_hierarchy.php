<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Изменить структуру regions: сделать city_id nullable (регионы могут быть корневыми)
        Schema::table('regions', function (Blueprint $table) {
            // Временно удаляем foreign key
            $table->dropForeign(['city_id']);
        });
        
        // Делаем city_id nullable и убираем unique с guid
        Schema::table('regions', function (Blueprint $table) {
            // Делаем city_id nullable
            $table->foreignId('city_id')->nullable()->change();
        });
        
        // Убираем unique constraint с guid отдельно (если существует)
        try {
            DB::statement('ALTER TABLE `regions` DROP INDEX `regions_guid_unique`');
        } catch (\Exception $e) {
            // Индекс может не существовать или иметь другое имя
            // Пытаемся найти и удалить через information_schema
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'regions' 
                AND INDEX_NAME LIKE '%guid%'
            ");
            foreach ($indexes as $index) {
                try {
                    DB::statement("ALTER TABLE `regions` DROP INDEX `{$index->INDEX_NAME}`");
                } catch (\Exception $e2) {
                    // Игнорируем ошибки
                }
            }
        }
        
        // 2. Добавить region_id в cities (город принадлежит региону)
        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('id')->constrained('regions')->onDelete('set null');
            $table->index(['region_id', 'is_active']);
        });
        
        // 3. Обновить индекс в regions
        Schema::table('regions', function (Blueprint $table) {
            $table->index(['guid']);
            $table->index(['city_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем region_id из cities
        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropIndex(['region_id', 'is_active']);
            $table->dropColumn('region_id');
        });
        
        // Возвращаем структуру regions
        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex(['guid']);
            $table->string('guid')->unique()->change();
        });
        
        Schema::table('regions', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable(false)->change();
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }
};

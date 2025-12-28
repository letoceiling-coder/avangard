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
        Schema::table('images', function (Blueprint $table) {
            // Проверяем существование колонок перед добавлением
            if (!Schema::hasColumn('images', 'is_available')) {
                $table->boolean('is_available')->default(true)->after('is_main');
            }
            
            if (!Schema::hasColumn('images', 'checked_at')) {
                $table->timestamp('checked_at')->nullable()->after('is_available');
            }
            
            if (!Schema::hasColumn('images', 'last_error')) {
                $table->text('last_error')->nullable()->after('checked_at');
            }
        });
        
        // Добавляем индекс для проверки доступности, если его еще нет
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            try {
                $indexes = DB::select("SHOW INDEXES FROM `images` WHERE Key_name = 'images_is_available_checked_at_index'");
                if (empty($indexes)) {
                    Schema::table('images', function (Blueprint $table) {
                        $table->index(['is_available', 'checked_at'], 'images_is_available_checked_at_index');
                    });
                }
            } catch (\Exception $e) {
                // Игнорируем ошибку, если индекс уже существует
            }
        } else {
            // Для других драйверов просто добавляем индекс
            try {
                Schema::table('images', function (Blueprint $table) {
                    $table->index(['is_available', 'checked_at']);
                });
            } catch (\Exception $e) {
                // Игнорируем ошибку
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            // Удаляем индекс
            try {
                $table->dropIndex(['is_available', 'checked_at']);
            } catch (\Exception $e) {
                // Игнорируем ошибку, если индекс не существует
            }
            
            // Удаляем колонки
            if (Schema::hasColumn('images', 'last_error')) {
                $table->dropColumn('last_error');
            }
            
            if (Schema::hasColumn('images', 'checked_at')) {
                $table->dropColumn('checked_at');
            }
            
            if (Schema::hasColumn('images', 'is_available')) {
                $table->dropColumn('is_available');
            }
        });
    }
};

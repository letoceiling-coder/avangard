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
        $driver = DB::connection()->getDriverName();
        
        // 1. Изменить структуру regions: сделать city_id nullable (регионы могут быть корневыми)
        if ($driver === 'mysql') {
            // Проверяем, есть ли foreign key constraint для city_id
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'regions' 
                    AND COLUMN_NAME = 'city_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                if (!empty($foreignKeys)) {
                    Schema::table('regions', function (Blueprint $table) {
                        // Временно удаляем foreign key
                        $table->dropForeign(['city_id']);
                    });
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки
            }
            
            // Делаем city_id nullable (проверяем, не является ли он уже nullable)
            $cityIdColumn = DB::select("
                SELECT IS_NULLABLE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'regions' 
                AND COLUMN_NAME = 'city_id'
            ");
            if (!empty($cityIdColumn) && $cityIdColumn[0]->IS_NULLABLE === 'NO') {
                Schema::table('regions', function (Blueprint $table) {
                    // Делаем city_id nullable
                    $table->foreignId('city_id')->nullable()->change();
                });
            }
            
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
        } else {
            // Для SQLite просто пытаемся изменить структуру
            // SQLite не поддерживает изменение колонок напрямую, но можно попробовать
            try {
                Schema::table('regions', function (Blueprint $table) {
                    // Для SQLite изменение nullable может не работать, но попробуем
                    if (Schema::hasColumn('regions', 'city_id')) {
                        // В SQLite изменение структуры ограничено
                        // Оставляем как есть, если колонка уже существует
                    }
                });
            } catch (\Exception $e) {
                // Игнорируем ошибки для SQLite
            }
        }
        
        // 2. Добавить region_id в cities (город принадлежит региону)
        if (!Schema::hasColumn('cities', 'region_id')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->foreignId('region_id')->nullable()->after('id')->constrained('regions')->onDelete('set null');
                $table->index(['region_id', 'is_active']);
            });
        } else {
            // Колонка уже существует, проверяем индекс
            if ($driver === 'mysql') {
                try {
                    $regionIdIndexExists = DB::select("SHOW INDEXES FROM `cities` WHERE Key_name = 'cities_region_id_is_active_index'");
                    if (empty($regionIdIndexExists)) {
                        Schema::table('cities', function (Blueprint $table) {
                            $table->index(['region_id', 'is_active']);
                        });
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки
                }
            } else {
                // Для SQLite просто пытаемся добавить индекс
                try {
                    Schema::table('cities', function (Blueprint $table) {
                        $table->index(['region_id', 'is_active']);
                    });
                } catch (\Exception $e) {
                    // Индекс уже существует - игнорируем
                }
            }
        }
        
        // 3. Обновить индекс в regions (проверяем существование перед созданием)
        // Добавляем индекс для guid, если его нет
        try {
            if ($driver === 'mysql') {
                $guidIndexExists = DB::select("SHOW INDEXES FROM `regions` WHERE Key_name = 'regions_guid_index'");
                if (empty($guidIndexExists)) {
                    Schema::table('regions', function (Blueprint $table) {
                        $table->index(['guid'], 'regions_guid_index');
                    });
                }
            } else {
                // Для SQLite просто добавляем индекс
                Schema::table('regions', function (Blueprint $table) {
                    $table->index(['guid'], 'regions_guid_index');
                });
            }
        } catch (\Exception $e) {
            // Индекс уже существует - игнорируем
        }
        
        // Добавляем индекс для city_id и is_active, если его нет
        try {
            if ($driver === 'mysql') {
                $cityIdIndexExists = DB::select("SHOW INDEXES FROM `regions` WHERE Key_name = 'regions_city_id_is_active_index'");
                if (empty($cityIdIndexExists)) {
                    Schema::table('regions', function (Blueprint $table) {
                        $table->index(['city_id', 'is_active'], 'regions_city_id_is_active_index');
                    });
                }
            } else {
                // Для SQLite просто добавляем индекс
                Schema::table('regions', function (Blueprint $table) {
                    $table->index(['city_id', 'is_active'], 'regions_city_id_is_active_index');
                });
            }
        } catch (\Exception $e) {
            // Индекс уже существует - игнорируем
        }
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

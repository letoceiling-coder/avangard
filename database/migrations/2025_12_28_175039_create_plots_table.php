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
        
        Schema::create('plots', function (Blueprint $table) use ($driver) {
            $table->id();
            
            // Связи
            $table->foreignId('village_id')->nullable()->constrained('villages')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->foreignId('builder_id')->nullable()->constrained('builders')->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            
            // Основные данные
            $table->string('guid')->nullable();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->string('external_id')->nullable();
            $table->string('crm_id')->nullable();
            
            // Координаты
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Цены (в копейках)
            $table->bigInteger('min_price')->nullable();
            $table->bigInteger('max_price')->nullable();
            
            // Площадь
            $table->decimal('area_min', 10, 2)->nullable();
            $table->decimal('area_max', 10, 2)->nullable();
            
            // Статус
            $table->integer('status')->default(1);
            $table->boolean('is_active')->default(true);
            
            // Источник данных
            $table->string('data_source')->default('parser'); // parser, manual, feed
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            // Метаданные
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['city_id', 'is_active']);
            $table->index(['village_id', 'is_active']);
            $table->index(['guid', 'city_id'], 'plots_guid_city_id_index');
            $table->index('external_id', 'plots_external_id_index');
            
            // Fulltext индекс только для MySQL/MariaDB
            if ($driver === 'mysql') {
                $table->fullText(['name', 'address']);
            } else {
                $table->index(['name', 'address']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};

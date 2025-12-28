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
        
        Schema::create('commercial_premises', function (Blueprint $table) use ($driver) {
            $table->id();
            
            // Связи
            $table->foreignId('commercial_block_id')->nullable()->constrained('commercial_blocks')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->foreignId('builder_id')->nullable()->constrained('builders')->onDelete('set null');
            $table->foreignId('district_id')->nullable()->constrained('regions')->onDelete('set null');
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
            $table->bigInteger('price')->nullable();
            $table->string('price_unit')->nullable(); // за м², за объект
            
            // Площадь
            $table->decimal('area', 10, 2)->nullable();
            
            // Тип помещения
            $table->string('premise_type')->nullable();
            $table->json('property_types')->nullable();
            
            // Статус
            $table->integer('status')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_booked')->default(false);
            
            // Источник данных
            $table->string('data_source')->default('parser');
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            // Метаданные
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['city_id', 'is_active'], 'commercial_premises_city_id_is_active_index');
            $table->index(['commercial_block_id', 'is_active'], 'commercial_premises_commercial_block_id_is_active_index');
            $table->index(['guid', 'city_id'], 'commercial_premises_guid_city_id_index');
            $table->index('external_id', 'commercial_premises_external_id_index');
            
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
        Schema::dropIfExists('commercial_premises');
    }
};

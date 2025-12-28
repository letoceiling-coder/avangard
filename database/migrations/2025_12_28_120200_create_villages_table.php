<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        Schema::create('villages', function (Blueprint $table) use ($driver) {
            $table->id();
            
            // Связи
            $table->foreignId('city_id')->constrained()->onDelete('restrict');
            $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
            
            // Основные данные
            $table->string('guid')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('external_id')->nullable()->index();
            
            // Статистика
            $table->unsignedInteger('plots_count')->default(0);
            $table->unsignedInteger('view_plots_count')->default(0);
            
            // Расстояния (JSON)
            $table->json('distance')->nullable();
            
            // Сроки и старт продаж
            $table->string('deadline')->nullable();
            $table->timestamp('deadline_date')->nullable();
            $table->string('sales_start')->nullable();
            $table->timestamp('sales_start_date')->nullable();
            
            // Комиссия
            $table->string('reward_label')->nullable();
            
            // Флаги
            $table->boolean('is_new_village')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Источник данных
            $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            // Метаданные
            $table->json('metadata')->nullable();
            $table->json('property_types')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['city_id', 'is_active']);
            $table->index(['builder_id', 'is_active']);
            $table->index(['data_source', 'parsed_at']);
            // Fulltext индекс только для MySQL/MariaDB
            if ($driver === 'mysql') {
                $table->fullText(['name', 'address']);
            } else {
                $table->index(['name', 'address']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};


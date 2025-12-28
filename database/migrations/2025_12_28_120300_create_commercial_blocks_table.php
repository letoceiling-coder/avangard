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
        Schema::create('commercial_blocks', function (Blueprint $table) use ($driver) {
            $table->id();
            
            // Связи
            $table->foreignId('city_id')->constrained()->onDelete('restrict');
            $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('district_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            
            // Основные данные
            $table->string('guid')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('external_id')->nullable()->index();
            
            // Статистика
            $table->unsignedInteger('premises_count')->default(0);
            $table->unsignedInteger('booked_premises_count')->default(0);
            
            // Флаги
            $table->boolean('is_new_block')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Сроки (JSON массивы)
            $table->json('deadlines')->nullable();
            $table->timestamp('deadline_date')->nullable();
            $table->boolean('deadline_over_check')->default(false);
            $table->json('sales_start_at')->nullable();
            
            // Комиссия
            $table->string('reward_label')->nullable();
            
            // Источник данных
            $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            // Метаданные
            $table->json('metadata')->nullable();
            $table->json('property_types')->nullable();
            $table->json('min_prices')->nullable(); // Цены по назначениям
            
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
        Schema::dropIfExists('commercial_blocks');
    }
};


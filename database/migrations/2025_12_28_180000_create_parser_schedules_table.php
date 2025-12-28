<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parser_schedules', function (Blueprint $table) {
            $table->id();
            
            // Тип объекта для парсинга
            $table->string('object_type', 50)->index(); 
            // blocks, parkings, villages, plots, commercial-blocks, commercial-premises, builders
            
            // Города для парсинга (JSON массив ID городов или NULL для всех активных)
            $table->json('city_ids')->nullable();
            
            // Временной диапазон работы парсера
            $table->time('time_from');
            $table->time('time_to');
            
            // Дни недели (JSON массив: [1,2,3,4,5] где 1=Понедельник, или NULL для всех дней)
            $table->json('days_of_week')->nullable();
            
            // Настройки парсинга
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('check_images')->default(false);
            $table->boolean('force_update')->default(false);
            $table->integer('limit')->default(1000)->comment('Лимит объектов на тип');
            $table->integer('offset')->default(0)->comment('Смещение для пагинации');
            $table->boolean('skip_errors')->default(false);
            
            // Последний запуск
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            
            // Статистика последнего запуска
            $table->integer('last_run_total')->nullable();
            $table->integer('last_run_created')->nullable();
            $table->integer('last_run_updated')->nullable();
            $table->integer('last_run_errors')->nullable();
            
            // Метаданные
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['is_active', 'object_type']);
            $table->index('next_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parser_schedules');
    }
};

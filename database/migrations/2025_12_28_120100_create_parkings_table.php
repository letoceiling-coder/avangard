<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parkings', function (Blueprint $table) {
            $table->id();
            
            // Связи
            $table->foreignId('block_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('city_id')->constrained()->onDelete('restrict');
            $table->foreignId('district_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
            
            // Основные данные
            $table->string('external_id')->nullable()->index();
            $table->string('block_guid')->nullable();
            $table->string('block_name')->nullable();
            $table->string('number')->nullable();
            $table->integer('floor')->nullable(); // Этаж (может быть отрицательным)
            $table->decimal('area', 8, 2)->nullable(); // Площадь в м²
            
            // Координаты
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Типы и статусы
            $table->string('parking_type')->nullable(); // "Подземный", "Наземный"
            $table->string('place_type')->nullable(); // "Увеличенное", "Стандартное"
            $table->string('property_type')->nullable(); // "new", "secondary"
            $table->string('status')->default('available'); // "available", "booked"
            $table->string('status_label')->nullable();
            
            // Цена и комиссия
            $table->unsignedBigInteger('price')->nullable(); // В копейках
            $table->string('reward_label')->nullable();
            
            // Сроки
            $table->string('deadline')->nullable();
            $table->timestamp('deadline_date')->nullable();
            $table->boolean('deadline_over_check')->default(false);
            
            // Источник данных
            $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            // Метаданные
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['block_id', 'status']);
            $table->index(['city_id', 'status']);
            $table->index(['data_source', 'parsed_at']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parkings');
    }
};


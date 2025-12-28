<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            
            // Полиморфная связь с объектом (Block, Parking, Village, CommercialBlock)
            $table->morphs('priceable');
            
            // Тип цены (min, max, fixed)
            $table->enum('price_type', ['min', 'max', 'fixed'])->default('fixed');
            
            // Старая и новая цена в копейках
            $table->unsignedBigInteger('old_price')->nullable();
            $table->unsignedBigInteger('new_price')->nullable();
            
            // Процент изменения (может быть отрицательным для снижения)
            $table->decimal('change_percent', 8, 2)->nullable();
            
            // Абсолютное изменение в копейках
            $table->bigInteger('change_amount')->nullable();
            
            // Источник изменения
            $table->string('source')->default('parser');
            
            // Пользователь, который внес изменение (если было вручную)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Время изменения
            $table->timestamp('changed_at');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['priceable_type', 'priceable_id', 'changed_at']);
            $table->index(['price_type', 'changed_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};

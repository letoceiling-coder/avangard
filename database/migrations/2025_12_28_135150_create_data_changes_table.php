<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_changes', function (Blueprint $table) {
            $table->id();
            
            // Полиморфная связь с объектом (Block, Parking, Village, CommercialBlock)
            $table->morphs('changeable');
            
            // Название измененного поля
            $table->string('field_name');
            
            // Старое и новое значение (JSON для сложных типов)
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            
            // Тип изменения (price, status, important, other)
            $table->enum('change_type', ['price', 'status', 'important', 'other'])->default('other');
            
            // Источник изменения (parser, manual, feed, import)
            $table->string('source')->default('parser');
            
            // Пользователь, который внес изменение (если было вручную)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Время изменения
            $table->timestamp('changed_at');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['changeable_type', 'changeable_id', 'changed_at']);
            $table->index(['change_type', 'changed_at']);
            $table->index(['field_name', 'changed_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_changes');
    }
};

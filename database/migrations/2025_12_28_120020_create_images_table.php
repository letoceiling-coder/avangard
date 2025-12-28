<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            
            // Полиморфная связь
            $table->morphs('imageable'); // imageable_type, imageable_id
            
            // Данные изображения
            $table->string('external_id')->nullable()->index(); // MongoDB _id
            $table->string('path')->nullable(); // Путь на CDN
            $table->string('file_name');
            $table->string('url_thumbnail')->nullable();
            $table->string('url_full')->nullable();
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            
            // Метаданные
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // Размер в байтах
            $table->string('mime_type')->nullable();
            
            // Локальное хранилище (если загружено)
            $table->string('local_path')->nullable();
            $table->string('disk')->default('public');
            
            // Сортировка
            $table->integer('sort_order')->default(0);
            $table->boolean('is_main')->default(false); // Главное изображение
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['imageable_type', 'imageable_id', 'sort_order']);
            $table->index(['is_main', 'imageable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};


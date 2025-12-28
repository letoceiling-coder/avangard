<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parser_errors', function (Blueprint $table) {
            $table->id();
            
            // Тип ошибки и источник
            $table->string('error_type')->index(); // 'api', 'parsing', 'validation', 'database'
            $table->string('object_type')->nullable()->index(); // 'block', 'parking', 'village', 'commercial_block'
            $table->string('source_type')->default('parser'); // 'parser', 'manual', 'feed', 'import'
            
            // Связь с объектом (если есть)
            $table->string('object_class')->nullable(); // Полное имя класса модели
            $table->unsignedBigInteger('object_id')->nullable();
            $table->string('external_id')->nullable()->index(); // External ID из API
            
            // Данные об ошибке
            $table->string('error_code')->nullable(); // Код ошибки
            $table->text('error_message'); // Сообщение об ошибке
            $table->text('error_details')->nullable(); // Детали (JSON или текст)
            $table->json('context')->nullable(); // Контекст ошибки (URL, параметры и т.д.)
            
            // API информация (если применимо)
            $table->string('api_url')->nullable();
            $table->integer('http_status_code')->nullable();
            $table->text('response_body')->nullable(); // Тело ответа API
            $table->string('request_method')->nullable(); // GET, POST и т.д.
            $table->json('request_params')->nullable(); // Параметры запроса
            
            // Статус обработки
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            
            // Количество попыток
            $table->unsignedInteger('attempts_count')->default(1);
            $table->timestamp('last_attempt_at')->useCurrent();
            
            // Метаданные
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            $table->index(['error_type', 'object_type', 'is_resolved']);
            $table->index(['created_at', 'is_resolved']);
            $table->index(['object_class', 'object_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parser_errors');
    }
};


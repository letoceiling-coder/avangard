<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['parser', 'manual', 'feed', 'import']);
            $table->string('source_name')->nullable(); // Название источника
            $table->string('source_file')->nullable(); // Имя файла (для feed)
            $table->morphs('sourceable'); // sourceable_type, sourceable_id (автоматически создает индекс)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->json('metadata')->nullable(); // Дополнительные данные
            $table->timestamp('processed_at');
            $table->timestamps();
            
            $table->index(['source_type', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};


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
        Schema::create('blocks', function (Blueprint $table) use ($driver) {
            $table->id();
            
            // Связи со справочниками
            $table->foreignId('city_id')->constrained()->onDelete('restrict');
            $table->foreignId('region_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('builder_id')->nullable()->constrained()->onDelete('set null');
            
            // Основные данные
            $table->string('guid')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->unsignedBigInteger('crm_id')->nullable();
            $table->string('external_id')->nullable()->index(); // MongoDB _id из API
            
            // Координаты
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Статусы и флаги
            $table->integer('status')->default(1); // 1 = активен
            $table->integer('edit_mode')->nullable();
            $table->boolean('is_suite')->default(false);
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_marked')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Цены (в копейках)
            $table->unsignedBigInteger('min_price')->nullable();
            $table->unsignedBigInteger('max_price')->nullable();
            
            // Статистика
            $table->unsignedInteger('apartments_count')->default(0);
            $table->unsignedInteger('view_apartments_count')->default(0);
            $table->unsignedInteger('exclusive_apartments_count')->default(0);
            
            // Сроки и отделка
            $table->string('deadline')->nullable();
            $table->timestamp('deadline_date')->nullable();
            $table->boolean('deadline_over_check')->default(false);
            $table->string('finishing')->nullable();
            
            // Источник данных
            $table->enum('data_source', ['parser', 'manual', 'feed', 'import'])->default('manual');
            $table->timestamp('parsed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            
            // JSON поля для гибких данных
            $table->json('metadata')->nullable();
            $table->json('advantages')->nullable();
            $table->json('payment_types')->nullable();
            $table->json('contract_types')->nullable();
            $table->json('installments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index(['city_id', 'is_active', 'status']);
            $table->index(['builder_id', 'is_active']);
            $table->index(['guid', 'city_id']);
            $table->index(['data_source', 'parsed_at']);
            $table->index(['latitude', 'longitude']);
            $table->index('is_exclusive');
            
            // Fulltext индекс только для MySQL/MariaDB
            if ($driver === 'mysql') {
                $table->fullText(['name', 'address']);
            } else {
                // Для других драйверов используем обычный индекс
                $table->index(['name', 'address']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};


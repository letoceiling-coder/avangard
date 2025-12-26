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
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token');
            $table->string('username')->nullable();
            $table->string('webhook_url')->nullable();
            $table->boolean('webhook_registered')->default(false);
            $table->text('welcome_message')->nullable();
            $table->json('settings')->nullable(); // Дополнительные настройки бота
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};

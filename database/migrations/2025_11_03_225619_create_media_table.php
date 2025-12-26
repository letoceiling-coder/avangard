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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('original_name');
            $table->string('extension',5);
            $table->string('disk')->default('uploads');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();


            $table->string('type')->default('photo'); // photo, video, document

            $table->unsignedBigInteger('size'); // размер в байтах

            $table->unsignedBigInteger('folder_id')->nullable();
            $table->foreign('folder_id')
                ->references('id')
                ->on('folders')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->string('telegram_file_id')->nullable(); // file_id из Telegram (если был загружен)

            $table->json('metadata')->nullable(); // дополнительные данные

            $table->boolean('temporary')->default(false);

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

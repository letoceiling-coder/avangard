<?php

use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('src')->default('folder');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('folders')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->timestamps();
        });
        $folders = [
            [
                'name' => 'Общая',
                'slug' => 'common',
                'src' => 'folder',
            ],
            [
                'name' => 'Видео',
                'slug' => 'video',
                'src' => 'video',
            ],
            [
                'name' => 'Документы',
                'slug' => 'document',
                'src' => 'document',
            ],
            [
                'name' => 'Корзина',
                'slug' => 'basket',
                'src' => 'basket',
            ],
        ];
        foreach ($folders as $folder) {
            Folder::create($folder);
        }


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};

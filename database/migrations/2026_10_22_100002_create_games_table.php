<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            // автор игры; при удалении пользователя удаляются его игры
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2)->default(0)->index();
            $table->string('file_path');
            $table->decimal('rating', 5, 2)->default(0)->index();
            $table->timestamps();

            $table->unique(['user_id', 'title']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};

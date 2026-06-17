<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            $table->unsignedTinyInteger('attempt_number'); // 1, 2, or 3
            $table->string('file_path', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('file_size')->default(0);

            $table->unsignedTinyInteger('score')->default(0);
            $table->json('ai_feedback')->nullable(); // full JSON from scorer
            $table->json('suggestions')->nullable(); // list of strings

            $table->timestamps();

            $table->unique(['post_id', 'attempt_number'], 'unique_post_attempt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_attempts');
    }
};

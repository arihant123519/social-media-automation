<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->tinyInteger('scope')->comment('0=YouTube, 1=Instagram');
            $table->string('post_type', 30)->comment('long_video|short_video|reels|photo|story');
            $table->string('keyword', 200);

            // Selected trending reference
            $table->string('trending_ref_id', 100);
            $table->json('trending_ref_meta');

            $table->unsignedTinyInteger('best_score')->default(0);
            $table->enum('final_status', ['in_progress', 'approved', 'max_attempts'])
                  ->default('in_progress');

            $table->timestamps();

            $table->index(['client_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

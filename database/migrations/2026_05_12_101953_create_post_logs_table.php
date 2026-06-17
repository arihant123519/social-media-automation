<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_scope_id')->constrained('client_scopes')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->tinyInteger('scope');           // 0=YouTube, 1=Instagram
            $table->string('post_type');            // long_video, short_video, story, photo, reels
            $table->date('scheduled_date');         // calculated post date
            $table->enum('status', ['pending', 'completed', 'missed'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();

            // Unique: ek din ek type ka ek hi log ho per scope
            $table->unique(['client_scope_id', 'post_type', 'scheduled_date'], 'unique_post_log');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('scope')->comment('0=YouTube, 1=Instagram, 2=Facebook');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('long_video')->default(0);
            $table->unsignedInteger('short_video')->default(0);
            $table->unsignedInteger('story')->default(0);
            $table->unsignedInteger('photo')->default(0);
            $table->unsignedInteger('reels')->default(0);
            $table->enum('status', ['pending', 'Published', 'missed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_scopes');
    }
};

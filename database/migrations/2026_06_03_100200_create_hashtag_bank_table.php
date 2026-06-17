<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #13 — Hashtag Bank per specialty.
 * Pre-built, performance-rated hashtags grouped by specialty, refreshed monthly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hashtag_bank', function (Blueprint $table) {
            $table->id();
            $table->string('specialty', 80)->index();            // e.g. dermatologist, ivf
            $table->string('tag', 100);                          // stored WITH leading #
            $table->enum('category', ['trending', 'niche', 'brand'])->default('niche');
            $table->enum('performance', ['high', 'medium', 'low'])->default('medium');
            $table->unsignedInteger('avg_reach')->nullable();    // optional analytics figure
            $table->string('notes', 255)->nullable();
            $table->timestamp('last_reviewed_at')->nullable();   // monthly refresh marker
            $table->timestamps();

            $table->unique(['specialty', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hashtag_bank');
    }
};

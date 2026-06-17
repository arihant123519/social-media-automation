<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #12 — Weekly AI caption drafts.
 * Populated every Sunday by `captions:generate-weekly` for next week's planned
 * slots. SMO Exec reviews/edits, then copies into the post creator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caption_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('scope')->comment('0=YouTube 1=Instagram 2=Facebook 3=LinkedIn');
            $table->string('post_type', 30);
            $table->date('scheduled_date');
            $table->string('theme', 60)->nullable();      // #14 weekday content theme
            $table->string('keyword', 200)->nullable();
            $table->text('caption')->nullable();
            $table->text('hashtags')->nullable();
            $table->enum('status', ['draft', 'edited', 'used', 'discarded'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'scope', 'post_type', 'scheduled_date'], 'caption_draft_slot_unique');
            $table->index(['client_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caption_drafts');
    }
};

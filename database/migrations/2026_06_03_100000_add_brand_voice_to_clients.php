<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #12 — Per-client Brand Voice document.
 * Fed into every Gemini caption/inspiration/scoring prompt so AI output
 * matches each client's tone, audience and do/don't rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('brand_voice')->nullable()->after('industry');
            $table->string('brand_tone', 120)->nullable()->after('brand_voice');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['brand_voice', 'brand_tone']);
        });
    }
};

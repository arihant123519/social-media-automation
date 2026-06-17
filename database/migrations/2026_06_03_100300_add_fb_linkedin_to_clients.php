<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #11 — Per-client Facebook Page + LinkedIn publishing credentials.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('fb_page_id', 100)->nullable()->after('ig_business_id');
            $table->text('fb_page_token')->nullable()->after('fb_page_id');
            $table->text('linkedin_token')->nullable()->after('fb_page_token');
            $table->string('linkedin_author_urn', 191)->nullable()->after('linkedin_token');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['fb_page_id', 'fb_page_token', 'linkedin_token', 'linkedin_author_urn']);
        });
    }
};

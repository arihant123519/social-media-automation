<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-client publishing config + OAuth tokens
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('auto_publish_enabled')->default(false)->after('status');

            // Instagram Business / Graph API
            $table->text('ig_access_token')->nullable()->after('auto_publish_enabled');
            $table->string('ig_business_id', 64)->nullable()->after('ig_access_token');

            // YouTube Data API
            $table->text('yt_refresh_token')->nullable()->after('ig_business_id');
            $table->text('yt_access_token')->nullable()->after('yt_refresh_token');
            $table->timestamp('yt_token_expires_at')->nullable()->after('yt_access_token');
            $table->string('yt_channel_id', 64)->nullable()->after('yt_token_expires_at');
        });

        // Publish status on each post
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('scheduled_publish_at')->nullable()->after('best_score');
            $table->timestamp('published_at')->nullable()->after('scheduled_publish_at');
            $table->string('external_post_id', 100)->nullable()->after('published_at');
            $table->string('external_url', 500)->nullable()->after('external_post_id');
            $table->enum('publish_status', [
                'not_ready',     // not approved yet
                'ready',         // approved, awaiting publish
                'scheduled',     // queued for scheduled date
                'publishing',    // in-flight
                'published',     // success
                'failed',        // API failure
                'dry_run',       // simulated (no real publish)
            ])->default('not_ready')->after('external_url');
            $table->text('publish_error')->nullable()->after('publish_status');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_publish_at', 'published_at',
                'external_post_id', 'external_url',
                'publish_status', 'publish_error',
            ]);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'auto_publish_enabled',
                'ig_access_token', 'ig_business_id',
                'yt_refresh_token', 'yt_access_token', 'yt_token_expires_at', 'yt_channel_id',
            ]);
        });
    }
};

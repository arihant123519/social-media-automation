<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Allow a Post to exist WITHOUT a trending reference.
 *
 * The Post Creator can now be reached from the AI Studio tools with a
 * pre-filled caption/hashtags, where picking a trending post to compare
 * against is optional. When skipped, the post is scored standalone — so
 * trending_ref_id / trending_ref_meta must be nullable.
 *
 * Raw ALTER is used (instead of Blueprint->change()) so this runs without
 * requiring the doctrine/dbal package.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE posts MODIFY trending_ref_id VARCHAR(100) NULL');
        DB::statement('ALTER TABLE posts MODIFY trending_ref_meta JSON NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE posts MODIFY trending_ref_id VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE posts MODIFY trending_ref_meta JSON NOT NULL');
    }
};

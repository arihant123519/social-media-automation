<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_attempts', function (Blueprint $table) {
            $table->text('caption')->nullable()->after('file_size');
            $table->string('hashtags', 600)->nullable()->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('post_attempts', function (Blueprint $table) {
            $table->dropColumn(['caption', 'hashtags']);
        });
    }
};

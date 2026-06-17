<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('keyword');
            $table->foreignId('client_scope_id')->nullable()->after('scheduled_date')
                  ->constrained('client_scopes')->nullOnDelete();
            $table->foreignId('post_log_id')->nullable()->after('client_scope_id')
                  ->constrained('post_logs')->nullOnDelete();

            $table->index(['client_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['client_scope_id']);
            $table->dropForeign(['post_log_id']);
            $table->dropIndex(['client_id', 'scheduled_date']);
            $table->dropColumn(['scheduled_date', 'client_scope_id', 'post_log_id']);
        });
    }
};

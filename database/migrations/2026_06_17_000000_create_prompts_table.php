<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // stable code-side identifier
            $table->string('name');                    // human label
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->longText('template');              // body with {{ placeholders }}
            $table->json('variables')->nullable();     // [{name, description}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->foreignId('editor_id')->constrained('users');
            $table->unsignedInteger('revision_number');
            $table->string('change_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at: this table is append-only. Rows are never updated.
            // Adding updated_at would allow Eloquent to silently mutate revision history.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_revisions');
    }
};

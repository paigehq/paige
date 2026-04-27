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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->string('action');
            $table->boolean('granted')->default(true);
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'space_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};

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
        Schema::create('page_ancestors', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('ancestor_id')->constrained('pages')->cascadeOnDelete();
            $table->unsignedInteger('depth');
            $table->primary(['page_id', 'ancestor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_ancestors');
    }
};

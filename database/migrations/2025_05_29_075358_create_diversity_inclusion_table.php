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
        Schema::create('diversity_inclusion', function (Blueprint $table) {
            $table->id('diversity_id');
            $table->string('home_page', 255)->nullable();
            $table->string('diversity_category', 255);
            $table->text('description')->nullable();
            $table->string('pdf_file', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diversity_inclusion');
    }
};
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
        Schema::create('diversity_home', function (Blueprint $table) {
            $table->id('dhome_id'); // Primary key
            $table->string('heading')->nullable();
            $table->text('description')->nullable();
            $table->string('home_img')->nullable();
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diversity_home');
    }
};
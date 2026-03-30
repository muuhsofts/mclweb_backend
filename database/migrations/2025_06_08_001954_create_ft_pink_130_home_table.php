<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ft_pink_130_home', function (Blueprint $table) {
            $table->id('ft_pink_id');
            $table->string('heading')->nullable();
            $table->text('description')->nullable();
            $table->string('home_img')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ft_pink_130_home');
    }
};
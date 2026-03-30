<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcl_home', function (Blueprint $table) {
            $table->id('mcl_home_id');
            $table->string('heading')->nullable();
            $table->string('mcl_home_img')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcl_home');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcl_pink130s', function (Blueprint $table) {
            $table->id('mcl_id');
            $table->string('home_page', 255)->nullable();
            $table->string('mclPink_category', 255);
            $table->text('description')->nullable();
            $table->string('video_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcl_pink130s');
    }
};
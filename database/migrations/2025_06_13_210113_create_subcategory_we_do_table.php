<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategory_we_do', function (Blueprint $table) {
            $table->id('subcategory_id');
            $table->foreignId('what_we_do_id');
            $table->string('subcategory', 255);
            $table->text('description')->nullable();
            $table->string('img_url')->nullable();
            $table->string('web_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategory_we_do');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcl_groups', function (Blueprint $table) {
            $table->id('mcl_id');
            $table->string('mcl_category');
            $table->string('image_file')->nullable();
            $table->text('description')->nullable();
            $table->string('weblink')->nullable();
            $table->boolean('home_page')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcl_groups');
    }
};
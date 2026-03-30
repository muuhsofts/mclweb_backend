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
        Schema::create('ft_groups', function (Blueprint $table) {
            $table->increments('ft_id');
            $table->string('home_page')->nullable();
            $table->string('ft_category');
            $table->string('image_file')->nullable();
            $table->text('description')->nullable();
            $table->string('weblink')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ft_groups');
    }
};
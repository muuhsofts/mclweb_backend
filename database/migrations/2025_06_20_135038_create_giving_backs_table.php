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
        Schema::create('giving_backs', function (Blueprint $table) {
           $table->id('giving_id'); // Custom primary key
        $table->string('givingBack_category');
        $table->text('description')->nullable();
        $table->string('weblink')->nullable();
        $table->json('image_slider')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giving_backs');
    }
};

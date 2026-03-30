<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadership', function (Blueprint $table) {
            $table->id('leadership_id');
            $table->string('position', 255)->nullable();
            $table->string('leader_name', 255);
             $table->string('level');
            $table->string('leader_image', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadership');
    }
};
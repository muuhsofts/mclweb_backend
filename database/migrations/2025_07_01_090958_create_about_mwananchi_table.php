<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAboutMwananchiTable extends Migration
{
    public function up()
    {
        Schema::create('about_mwananchi', function (Blueprint $table) {
            $table->id();
            $table->string('category', 255);
            $table->text('description')->nullable();
             $table->text('pdf_file')->nullable();
            $table->string('video_link')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('about_mwananchi');
    }
}
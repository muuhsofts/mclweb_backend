<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEarlyCareersTable extends Migration
{
    public function up()
    {
        Schema::create('early_careers', function (Blueprint $table) {
            $table->id('early_career_id');
            $table->string('category');
            $table->string('img_file')->nullable();
            $table->string('video_file')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('early_careers');
    }
}

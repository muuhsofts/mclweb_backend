<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEarycareHomeTable extends Migration
{
    public function up()
    {
        Schema::create('earycare_home', function (Blueprint $table) {
            $table->id('earycare_id');
            $table->text('description')->nullable();
            $table->string('heading', 255)->nullable();
            $table->string('home_img')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('earycare_home');
    }
}
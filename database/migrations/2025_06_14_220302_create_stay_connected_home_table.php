<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStayConnectedHomeTable extends Migration
{
    public function up()
    {
        Schema::create('stay_connected_home', function (Blueprint $table) {
            $table->id('stay_connected_id');
            $table->text('description')->nullable();
            $table->string('heading', 255)->nullable();
            $table->string('home_img')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stay_connected_home');
    }
}
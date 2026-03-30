<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStayConnectedTable extends Migration
{
    public function up()
    {
        Schema::create('stay_connected', function (Blueprint $table) {
            $table->id('stay_connected_id');
            $table->string('category');
            $table->string('img_file')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stay_connected');
    }
}

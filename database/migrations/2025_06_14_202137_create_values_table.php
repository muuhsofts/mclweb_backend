<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateValuesTable extends Migration
{
    public function up()
    {
        Schema::create('values', function (Blueprint $table) {
            $table->id('value_id');
            $table->string('category');
            $table->string('img_file')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('values');
    }
}

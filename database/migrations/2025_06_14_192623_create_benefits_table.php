<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBenefitsTable extends Migration
{
    public function up()
    {
        Schema::create('benefits', function (Blueprint $table) {
            $table->id('benefit_id');
            $table->string('category');
             $table->string('description',455);
            $table->string('img_file')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('benefits');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBenefitiesHomesTable extends Migration
{
    public function up()
    {
        Schema::create('benefities_homes', function (Blueprint $table) {
            $table->id('benefit_home_id');
            $table->string('description')->nullable();
            $table->string('heading', 455)->nullable();
            $table->string('home_img')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('benefities_homes');
    }
}
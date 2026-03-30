<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGivingBackTable extends Migration
{
    public function up()
    {
        Schema::create('giving_back', function (Blueprint $table) {
            $table->id('giving_id');
            $table->string('givingBack_category', 255);
            $table->text('description')->nullable();
            $table->string('weblink', 255)->nullable();
            $table->text('image_slider')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giving_back');
    }
}
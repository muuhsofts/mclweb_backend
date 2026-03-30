<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarksTable extends Migration
{
    public function up()
    {
        Schema::create('marks', function (Blueprint $table) {
            $table->id('mark_id'); // Primary key
            $table->unsignedBigInteger('user_id'); // Foreign key to users
             $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable(); // Optional foreign key to employees
            $table->integer('total_marks')->default(0); // Total marks for the user
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('marks');
    }
}
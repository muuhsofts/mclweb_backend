<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id('question_id'); // Primary key
            $table->unsignedBigInteger('item_id'); // Foreign key to items
            $table->json('question_category');
            $table->json('choice');
            $table->json('marks_caryy_that_choice');
            $table->json('marks_per_choice_attempted');
            $table->json('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('item_id')->references('item_id')->on('items')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('questions');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnswersTable extends Migration
{
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id('answer_id'); // Primary key
            $table->unsignedBigInteger('user_id'); // Foreign key to users
            $table->unsignedBigInteger('logged-user_id'); // Foreign key to users
            $table->unsignedBigInteger('question_id'); // Foreign key to questions
            $table->json('category_answers'); // JSON array for answers to each question_category
            $table->integer('marks_scored')->nullable(); // Marks scored, nullable until graded
            $table->integer('total_marks'); // Total marks for the answer
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('answers');
    }
}
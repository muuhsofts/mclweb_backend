<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_blogs', function (Blueprint $table) {
            $table->id('sublog_id');
            $table->string('heading', 255);
            $table->integer('blog_id', );
            $table->text('description')->nullable();
            $table->string('video_file')->nullable();
            $table->string('image_file')->nullable();
            $table->string('url_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_blogs');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sub_news', function (Blueprint $table) {
             $table->id('subnew_id');
            $table->foreignId('news_id');
            $table->string('img_url')->nullable();
            $table->string('heading', 255);
            $table->text('description')->nullable();
            $table->string('twitter_link', 255)->nullable();
            $table->string('facebook_link', 255)->nullable();
            $table->string('instagram_link', 255)->nullable();
            $table->string('email_url', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_news');
    }
};

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
        Schema::create('contact_info', function (Blueprint $table) {
            $table->id('contact_info_id'); // Creates an auto-incrementing primary key named 'contact_info_id'
            
             $table->unsignedBigInteger('contactus_id'); 
            $table->string('phone_one', 25);
            $table->string('phone_two', 25)->nullable(); // A second phone number might not always exist
            $table->string('email_address')->unique(); // Emails should be unique
            $table->string('webmail_address')->nullable();
            $table->string('location');
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_info');
    }
};
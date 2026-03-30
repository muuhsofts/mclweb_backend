
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSustainabilityTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sustainability', function (Blueprint $table) {
            $table->id('sustain_id');
            $table->string('sustain_category', 255);
            $table->text('description')->nullable();
            $table->string('weblink', 255)->nullable();
            $table->string('sustain_pdf_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sustainability');
    }
}

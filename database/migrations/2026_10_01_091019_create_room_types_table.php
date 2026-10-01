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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Deluxe Suite, Superior Twin
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2); // Base nightly rate
            $table->integer('capacity'); // Max occupants
            $table->string('bed_type')->nullable(); // King, Queen, Twin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
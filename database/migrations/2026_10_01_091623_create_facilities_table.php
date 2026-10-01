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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Infinity Pool, Gym, Spa
            $table->text('description')->nullable();
            $table->string('location')->nullable(); // e.g. 3rd Floor, Rooftop, Garden Area
            $table->time('opening_time')->nullable(); // e.g. 06:00:00
            $table->time('closing_time')->nullable(); // e.g. 22:00:00
            $table->decimal('price', 10, 2)->default(0.00); // 0.00 if free for hotel guests
            $table->enum('status', ['open', 'closed', 'maintenance'])->default('open');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
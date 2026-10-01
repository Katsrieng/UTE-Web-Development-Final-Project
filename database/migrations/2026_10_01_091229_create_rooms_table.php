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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            // Foreign key to room_types table
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->string('room_number')->unique(); // e.g. 101, 204A
            $table->integer('floor')->default(1);
            $table->decimal('price_per_night', 10, 2);
            $table->enum('status', ['available', 'booked', 'occupied', 'maintenance', 'cleaning'])->default('available');
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // Thumbnail or cover photo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
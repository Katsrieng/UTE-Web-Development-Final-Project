<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NOTE: depends on the `bookings` table already existing (Katsrieng's module).
// Build this one last — rename the date prefix to run after bookings once merged.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('restrict');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 10, 2); // snapshot of package price at time of booking
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_packages');
    }
};

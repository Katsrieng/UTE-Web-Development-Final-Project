<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The booking FK is installed by the later deferred-FK migration.

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->index();
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

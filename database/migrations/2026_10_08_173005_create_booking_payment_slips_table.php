<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payment_slips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('file_path');          // storage path, e.g. payment-slips/booking-3-1728400000.jpg
            $table->string('original_filename');  // original name shown in UI
            $table->string('mime_type', 20);      // image/jpeg or image/png

            $table->boolean('reviewed')->default(false); // staff has seen/acted on it

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payment_slips');
    }
};

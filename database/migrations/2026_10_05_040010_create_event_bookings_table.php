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
        Schema::create('event_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 30);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('guest_count');
            $table->text('special_requests')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('status_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['venue_id', 'status', 'starts_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_bookings');
    }
};

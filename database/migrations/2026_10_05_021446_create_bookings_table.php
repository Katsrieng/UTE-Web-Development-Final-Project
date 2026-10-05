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
       Schema::create('bookings', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('room_id')->constrained('rooms');

    $table->date('check_in_date');
    $table->date('check_out_date');

    $table->integer('number_of_guests');
    $table->decimal('total_amount', 10, 2);

    $table->string('status')->default('Pending');
    $table->text('special_request')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

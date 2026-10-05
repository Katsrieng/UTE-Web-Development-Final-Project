<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Silver, Gold, Platinum
            $table->text('description')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0); // e.g. 10.00 = 10%
            $table->unsignedInteger('duration_months');    // membership length
            $table->string('status')->default('active');  // active | inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};

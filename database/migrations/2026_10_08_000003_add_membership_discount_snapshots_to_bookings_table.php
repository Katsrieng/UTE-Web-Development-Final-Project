<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('membership_name', 191)->nullable();
            $table->decimal('membership_discount_percentage', 5, 2)->default(0);
            $table->decimal('membership_discount_amount', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_id');
            $table->dropColumn(['membership_name', 'membership_discount_percentage', 'membership_discount_amount']);
        });
    }
};

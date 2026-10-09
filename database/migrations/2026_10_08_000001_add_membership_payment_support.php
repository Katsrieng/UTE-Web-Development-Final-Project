<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Membership tiers now have a price so joining can be paid for.
        Schema::table('membership_types', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('duration_months');
        });

        // A payment can now belong to a membership (instead of a booking / event).
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('membership_id')
                ->nullable()
                ->after('event_booking_id')
                ->constrained('memberships')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_id');
        });

        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};

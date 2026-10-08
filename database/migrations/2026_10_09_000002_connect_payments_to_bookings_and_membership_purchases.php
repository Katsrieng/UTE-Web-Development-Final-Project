<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve legacy records. Stop with an actionable error rather than silently rewriting orphan IDs.
        if (DB::table('payments')->whereNotNull('booking_id')->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('bookings')->whereColumn('bookings.id', 'payments.booking_id');
        })->exists()) {
            throw new RuntimeException('Legacy payments contain missing Booking references. Review those records before applying this migration.');
        }
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('booking_id', 'payments_booking_id_integrated_foreign')->references('id')->on('bookings')->restrictOnDelete();
            $table->foreignId('membership_purchase_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('transaction_reference', 191)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('payments_booking_id_integrated_foreign');
            $table->dropConstrainedForeignId('membership_purchase_id');
            $table->dropColumn('transaction_reference');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_packages') || ! Schema::hasTable('bookings')) {
            return;
        }
        foreach (Schema::getForeignKeys('booking_packages') as $key) {
            if ($key['columns'] === ['booking_id'] && $key['foreign_table'] === 'bookings') {
                return;
            }
        }
        Schema::table('booking_packages', function (Blueprint $table) {
            $table->foreign('booking_id', 'booking_packages_booking_id_deferred_foreign')->references('id')->on('bookings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Only remove the constraint installed here; preserve an existing database's original FK.
        if (! Schema::hasTable('booking_packages')) {
            return;
        }
        foreach (Schema::getForeignKeys('booking_packages') as $key) {
            if ($key['name'] === 'booking_packages_booking_id_deferred_foreign') {
                Schema::table('booking_packages', fn (Blueprint $table) => $table->dropForeign($key['name']));

                return;
            }
        }
    }
};

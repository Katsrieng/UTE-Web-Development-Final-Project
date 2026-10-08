<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (collect(Schema::getForeignKeys('payments'))->contains(fn ($key) => $key['columns'] === ['booking_id'])) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! collect(Schema::getForeignKeys('payments'))->contains(fn ($key) => $key['name'] === 'payments_booking_id_foreign')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });
    }
};

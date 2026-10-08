<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->foreignId('next_membership_type_id')->nullable()->constrained('membership_types')->nullOnDelete();
        });
        // Only current plan configuration changes; historical purchase/booking snapshots remain intact.
        foreach (['Silver' => [30, 5, 300, 'Gold'], 'Gold' => [60, 10, 700, 'Platinum'], 'Platinum' => [100, 15, null, null]] as $name => [$price, $discount, $threshold, $next]) {
            DB::table('membership_types')->where('name', $name)->update([
                'price' => $price, 'discount_percentage' => $discount, 'duration_months' => 12,
                'loyalty_upgrade_points' => $threshold,
                'next_membership_type_id' => $next ? DB::table('membership_types')->where('name', $next)->orderBy('id')->value('id') : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('membership_types', fn (Blueprint $table) => $table->dropConstrainedForeignId('next_membership_type_id'));
    }
};

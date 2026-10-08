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
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('loyalty_upgrade_points')->nullable();
        });

        // Initialize only the new plan metadata; preserve existing tiers, rates and memberships.
        foreach (['Silver' => [20, 300], 'Gold' => [40, 700], 'Platinum' => [70, null]] as $name => [$price, $points]) {
            DB::table('membership_types')->where('name', $name)->update(['price' => $price, 'loyalty_upgrade_points' => $points]);
        }
    }

    public function down(): void
    {
        Schema::table('membership_types', fn (Blueprint $table) => $table->dropColumn(['price', 'loyalty_upgrade_points']));
    }
};

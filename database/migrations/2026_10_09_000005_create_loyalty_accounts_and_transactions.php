<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->bigInteger('balance')->default(0);
            $table->timestamps();
        });
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20);
            $table->bigInteger('points');
            $table->bigInteger('resulting_balance');
            $table->unsignedBigInteger('source_amount_cents')->nullable();
            $table->string('old_tier_name', 191)->nullable();
            $table->string('new_tier_name', 191)->nullable();
            $table->timestamps();
            $table->unique(['payment_id', 'kind']);
            $table->index(['loyalty_account_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
    }
};

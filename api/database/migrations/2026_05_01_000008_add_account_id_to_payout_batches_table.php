<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_batches', function (Blueprint $table): void {
            // Payout batches are per-account: each account has its own
            // open/close lifecycle and only claims its own commission
            // ledger entries (see ClosePayoutBatch).
            $table->foreignId('account_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payout_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};

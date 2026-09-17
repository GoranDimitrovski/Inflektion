<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_batch_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payout_batch_id')->constrained();
            $table->foreignId('commission_ledger_entry_id')->constrained()->unique();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_batch_entries');
    }
};

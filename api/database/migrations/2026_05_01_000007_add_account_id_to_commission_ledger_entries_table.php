<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_ledger_entries', function (Blueprint $table): void {
            $table->foreignId('account_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commission_ledger_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};

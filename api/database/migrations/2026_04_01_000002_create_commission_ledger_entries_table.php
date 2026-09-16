<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversion_id')->constrained();
            $table->foreignId('program_id')->constrained();
            $table->bigInteger('amount_minor_units');
            $table->char('amount_currency', 3);
            $table->string('type')->default('commission');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_ledger_entries');
    }
};

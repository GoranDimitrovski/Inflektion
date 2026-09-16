<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table): void {
            $table->string('commission_strategy')->nullable()->after('status');
            $table->decimal('commission_rate', 8, 4)->nullable()->after('commission_strategy');
            $table->bigInteger('commission_flat_amount_minor_units')->nullable()->after('commission_rate');
            $table->char('commission_flat_amount_currency', 3)->nullable()->after('commission_flat_amount_minor_units');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table): void {
            $table->dropColumn([
                'commission_strategy',
                'commission_rate',
                'commission_flat_amount_minor_units',
                'commission_flat_amount_currency',
            ]);
        });
    }
};

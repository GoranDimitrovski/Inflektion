<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversions', function (Blueprint $table): void {
            // Nullable: a conversion arrives via postback before attribution
            // succeeds, so it starts genuinely unowned. AttributeConversion
            // sets this alongside attributed_program_id — until then, the
            // tenant scope correctly hides it from every account.
            $table->foreignId('account_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};

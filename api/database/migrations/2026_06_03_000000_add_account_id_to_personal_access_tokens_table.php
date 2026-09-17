<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            // Nullable: Sanctum's own tokens (e.g. test helpers created via
            // $user->createToken() outside a tenant context) have no account.
            // Every token this app's own issuance path creates always sets it.
            $table->foreignId('account_id')->nullable()->after('tokenable_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_id');
        });
    }
};

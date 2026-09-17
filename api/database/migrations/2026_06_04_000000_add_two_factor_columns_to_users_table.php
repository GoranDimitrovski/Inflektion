<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Same shape as Laravel Fortify's own published migration
// (vendor/laravel/fortify/database/migrations/..._add_two_factor_columns_to_users_table.php).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->after('password')->nullable();
            $table->text('two_factor_recovery_codes')->after('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->after('two_factor_recovery_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};

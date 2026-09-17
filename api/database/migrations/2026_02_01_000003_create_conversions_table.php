<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table): void {
            $table->id();
            $table->string('vendor');
            $table->string('external_id');
            $table->foreignId('click_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount_minor_units');
            $table->char('amount_currency', 3);
            $table->string('status')->default('recorded');
            $table->foreignId('attributed_program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['vendor', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};

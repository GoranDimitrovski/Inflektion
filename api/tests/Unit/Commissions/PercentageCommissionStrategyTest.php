<?php

declare(strict_types=1);

namespace Tests\Unit\Commissions;

use App\Commissions\CommissionException;
use App\Commissions\Strategies\PercentageCommissionStrategy;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PercentageCommissionStrategyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('amountsAndRates')]
    public function itCalculatesACommissionAsARateOfTheConversionAmount(int $amountMinorUnits, string $rate, int $expectedMinorUnits): void
    {
        $program = Program::factory()->create(['commission_rate' => $rate]);
        $conversion = Conversion::create([
            'vendor' => 'demo-store',
            'external_id' => 'ext-'.$amountMinorUnits.'-'.$rate,
            'amount' => Money::of($amountMinorUnits, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $program->id,
        ]);

        $strategy = new PercentageCommissionStrategy;
        $result = $strategy->calculate($conversion, $program);

        $this->assertEquals(Money::of($expectedMinorUnits, 'USD'), $result);
    }

    #[Test]
    public function itThrowsWhenTheProgramHasNoCommissionRateConfigured(): void
    {
        $program = Program::factory()->create(['commission_rate' => null]);
        $conversion = Conversion::create([
            'vendor' => 'demo-store',
            'external_id' => 'ext-missing-rate',
            'amount' => Money::of(1000, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $program->id,
        ]);

        $strategy = new PercentageCommissionStrategy;

        $this->expectException(CommissionException::class);

        $strategy->calculate($conversion, $program);
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: int}>
     */
    public static function amountsAndRates(): array
    {
        return [
            'zero rate' => [10000, '0.0000', 0],
            '10% of $100.00' => [10000, '0.1000', 1000],
            '5% of $49.99' => [4999, '0.0500', 250],
            '100% of $1.00' => [100, '1.0000', 100],
            '9.25% of a large amount stays exact' => [999_999_999, '0.0925', 92_500_000],
            '29% of $10.35, a value prone to float drift' => [1035, '0.2900', 300],
        ];
    }
}

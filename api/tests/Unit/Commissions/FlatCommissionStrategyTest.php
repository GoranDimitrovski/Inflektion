<?php

declare(strict_types=1);

namespace Tests\Unit\Commissions;

use App\Commissions\CommissionException;
use App\Commissions\Strategies\FlatCommissionStrategy;
use App\Models\Conversion;
use App\Models\Program;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FlatCommissionStrategyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('conversionAndFlatAmounts')]
    public function itReturnsTheProgramsFixedCommissionAmountRegardlessOfConversionAmount(int $conversionMinorUnits, int $flatMinorUnits): void
    {
        $program = Program::factory()->create([
            'commission_flat_amount' => Money::of($flatMinorUnits, 'USD'),
        ]);
        $conversion = Conversion::create([
            'vendor' => 'demo-store',
            'external_id' => 'ext-'.$conversionMinorUnits.'-'.$flatMinorUnits,
            'amount' => Money::of($conversionMinorUnits, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $program->id,
        ]);

        $strategy = new FlatCommissionStrategy;
        $result = $strategy->calculate($conversion, $program);

        $this->assertEquals(Money::of($flatMinorUnits, 'USD'), $result);
    }

    #[Test]
    public function itThrowsWhenTheProgramHasNoCommissionFlatAmountConfigured(): void
    {
        $program = Program::factory()->create(['commission_flat_amount' => null]);
        $conversion = Conversion::create([
            'vendor' => 'demo-store',
            'external_id' => 'ext-missing-flat',
            'amount' => Money::of(1000, 'USD'),
            'status' => 'attributed',
            'attributed_program_id' => $program->id,
        ]);

        $strategy = new FlatCommissionStrategy;

        $this->expectException(CommissionException::class);

        $strategy->calculate($conversion, $program);
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function conversionAndFlatAmounts(): array
    {
        return [
            'small conversion, flat $5.00' => [100, 500],
            'large conversion, flat $5.00' => [999999, 500],
        ];
    }
}

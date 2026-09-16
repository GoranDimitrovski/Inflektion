<?php

declare(strict_types=1);

namespace Tests\Unit\Commissions;

use App\Commissions\CommissionStrategyRegistry;
use App\Commissions\Strategies\FlatCommissionStrategy;
use App\Commissions\Strategies\PercentageCommissionStrategy;
use App\Commissions\UnknownCommissionStrategyException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CommissionStrategyRegistryTest extends TestCase
{
    #[Test]
    public function itResolvesAConfiguredStrategyIdentifierToItsStrategyInstance(): void
    {
        $registry = new CommissionStrategyRegistry([
            'percentage' => PercentageCommissionStrategy::class,
            'flat' => FlatCommissionStrategy::class,
        ]);

        $this->assertInstanceOf(PercentageCommissionStrategy::class, $registry->resolve('percentage'));
        $this->assertInstanceOf(FlatCommissionStrategy::class, $registry->resolve('flat'));
    }

    #[Test]
    public function itThrowsRatherThanSilentlyDefaultingForAnUnknownStrategyIdentifier(): void
    {
        $registry = new CommissionStrategyRegistry(['flat' => FlatCommissionStrategy::class]);

        $this->expectException(UnknownCommissionStrategyException::class);

        $registry->resolve('does-not-exist');
    }

    #[Test]
    public function itThrowsRatherThanSilentlyDefaultingWhenNoStrategyIdentifierIsConfigured(): void
    {
        $registry = new CommissionStrategyRegistry(['flat' => FlatCommissionStrategy::class]);

        $this->expectException(UnknownCommissionStrategyException::class);

        $registry->resolve(null);
    }
}

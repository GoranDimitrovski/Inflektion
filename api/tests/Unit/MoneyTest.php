<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneyTest extends TestCase
{
    #[Test]
    public function itConsidersEqualAmountAndCurrencyEqual(): void
    {
        $this->assertTrue(Money::of(100, 'USD')->equals(Money::of(100, 'USD')));
        $this->assertFalse(Money::of(100, 'USD')->equals(Money::of(100, 'EUR')));
        $this->assertFalse(Money::of(100, 'USD')->equals(Money::of(101, 'USD')));
    }

    #[Test]
    public function itRejectsACurrencyCodeThatIsNot3Letters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of(100, 'US');
    }

    #[Test]
    public function itNormalizesCurrencyToUppercaseViaTheNamedConstructor(): void
    {
        $this->assertSame('USD', Money::of(100, 'usd')->currency);
    }
}

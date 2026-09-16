<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Program;
use App\Support\Money;
use App\Support\MoneyCast;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneyCastTest extends TestCase
{
    #[Test]
    public function itCastsAValueObjectToTwoDbColumns(): void
    {
        $cast = new MoneyCast;

        $columns = $cast->set(new Program, 'price', Money::of(1000, 'USD'), []);

        $this->assertSame([
            'price_minor_units' => 1000,
            'price_currency' => 'USD',
        ], $columns);
    }

    #[Test]
    public function itHydratesAValueObjectFromTwoDbColumns(): void
    {
        $cast = new MoneyCast;

        $money = $cast->get(new Program, 'price', null, [
            'price_minor_units' => 1000,
            'price_currency' => 'USD',
        ]);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame(1000, $money->minorUnits);
        $this->assertSame('USD', $money->currency);
    }

    #[Test]
    public function itReturnsNullWhenTheUnderlyingColumnsAreNull(): void
    {
        $cast = new MoneyCast;

        $this->assertNull($cast->get(new Program, 'price', null, []));
        $this->assertSame([
            'price_minor_units' => null,
            'price_currency' => null,
        ], $cast->set(new Program, 'price', null, []));
    }

    #[Test]
    public function itRejectsANonMoneyValueOnSet(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MoneyCast)->set(new Program, 'price', 'not-money', []);
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Commerce;

use App\Support\Money;

final class CreateProgramData
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $status = null,
        public readonly ?string $commissionStrategy = null,
        public readonly ?string $commissionRate = null,
        public readonly ?Money $commissionFlatAmount = null,
    ) {}

    /**
     * @param  array{name: string, slug: string, status?: string, commissionStrategy?: string, commissionRate?: string, commissionFlatAmountMinorUnits?: int, commissionFlatAmountCurrency?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        $flatAmount = isset($data['commissionFlatAmountMinorUnits'], $data['commissionFlatAmountCurrency'])
            ? Money::of($data['commissionFlatAmountMinorUnits'], $data['commissionFlatAmountCurrency'])
            : null;

        return new self(
            name: $data['name'],
            slug: $data['slug'],
            status: $data['status'] ?? null,
            commissionStrategy: $data['commissionStrategy'] ?? null,
            commissionRate: $data['commissionRate'] ?? null,
            commissionFlatAmount: $flatAmount,
        );
    }
}

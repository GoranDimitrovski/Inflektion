<?php

declare(strict_types=1);

namespace App\Commissions;

final class CommissionStrategyRegistry
{
    /**
     * @param  array<string, class-string<CommissionStrategy>>  $strategies
     */
    public function __construct(
        private readonly array $strategies,
    ) {}

    /**
     * @return list<string>
     */
    public function knownIdentifiers(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * @throws CommissionException
     */
    public function resolve(?string $identifier): CommissionStrategy
    {
        if ($identifier === null || ! isset($this->strategies[$identifier])) {
            throw new CommissionException(
                $identifier === null
                    ? 'Program has no commission_strategy configured.'
                    : "Unknown commission strategy [{$identifier}]."
            );
        }

        /** @var CommissionStrategy */
        return app($this->strategies[$identifier]);
    }
}

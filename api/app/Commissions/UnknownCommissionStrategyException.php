<?php

declare(strict_types=1);

namespace App\Commissions;

use RuntimeException;

final class UnknownCommissionStrategyException extends RuntimeException
{
    public function __construct(?string $identifier)
    {
        parent::__construct(
            $identifier === null
                ? 'Program has no commission_strategy configured.'
                : "Unknown commission strategy [{$identifier}]."
        );
    }
}

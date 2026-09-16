<?php

declare(strict_types=1);

namespace App\Support;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', microtime(true)))
            ?: new \DateTimeImmutable;
    }
}

<?php

declare(strict_types=1);

namespace App\Personalization;

final class Variant
{
    /**
     * @param  array<string, string>  $queryParams
     */
    public function __construct(
        public readonly string $key,
        public readonly array $queryParams = [],
    ) {}

    public static function none(): self
    {
        return new self('default');
    }
}

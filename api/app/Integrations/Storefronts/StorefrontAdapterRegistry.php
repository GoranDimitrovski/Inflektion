<?php

declare(strict_types=1);

namespace App\Integrations\Storefronts;

final class StorefrontAdapterRegistry
{
    /**
     * @param  array<string, class-string<StorefrontAdapter>>  $adapters
     */
    public function __construct(
        private readonly array $adapters,
    ) {}

    public function resolve(string $vendor): StorefrontAdapter
    {
        if (! isset($this->adapters[$vendor])) {
            throw new UnknownStorefrontVendorException($vendor);
        }

        return app($this->adapters[$vendor]);
    }
}

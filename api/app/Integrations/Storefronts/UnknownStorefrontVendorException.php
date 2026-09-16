<?php

declare(strict_types=1);

namespace App\Integrations\Storefronts;

use InvalidArgumentException;

final class UnknownStorefrontVendorException extends InvalidArgumentException
{
    public function __construct(string $vendor)
    {
        parent::__construct("Unknown storefront vendor [{$vendor}].");
    }
}

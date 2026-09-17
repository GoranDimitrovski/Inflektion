<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Access\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = app(TenantContext::class);

            if ($tenant->hasAccount()) {
                $builder->where($builder->getModel()->getTable().'.account_id', $tenant->account()->id);
            }
        });
    }
}

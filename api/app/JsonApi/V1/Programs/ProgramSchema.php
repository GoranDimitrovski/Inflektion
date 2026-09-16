<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Programs;

use App\Models\Program;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class ProgramSchema extends Schema
{
    public static string $model = Program::class;

    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name'),
            Str::make('slug'),
            Str::make('status'),
            Str::make('commissionStrategy', 'commission_strategy'),
            Str::make('commissionRate', 'commission_rate'),
            Number::make('commissionFlatAmountMinorUnits', 'commission_flat_amount_minor_units'),
            Str::make('commissionFlatAmountCurrency', 'commission_flat_amount_currency'),
            DateTime::make('createdAt')->sortable()->readOnly(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    /**
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()->withDefaultPerPage(25);
    }

    // No auth/policy system yet; revisit once Program gets a Policy.
    public function authorizable(): bool
    {
        return false;
    }
}

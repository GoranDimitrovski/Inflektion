<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Links;

use App\Models\Link;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class LinkSchema extends Schema
{
    public static string $model = Link::class;

    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('programId', 'program_id')->readOnly(),
            Str::make('destinationUrl', 'destination_url'),
            Str::make('token')->readOnly(),
            Str::make('redirectUrl')
                ->readOnly()
                ->extractUsing(fn (Link $link): string => route('tracking.redirect', $link->token)),
            Str::make('status'),
            Str::make('personalizationStrategy', 'personalization_strategy'),
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
            Where::make('programId', 'program_id'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()->withDefaultPerPage(25);
    }

    public function authorizable(): bool
    {
        return true;
    }
}

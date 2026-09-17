<?php

declare(strict_types=1);

namespace App\JsonApi\V1\ApiTokens;

use App\Access\ApiToken;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class ApiTokenSchema extends Schema
{
    public static string $model = ApiToken::class;

    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name'),
            ArrayList::make('abilities'),
            DateTime::make('lastUsedAt', 'last_used_at')->readOnly(),
            DateTime::make('expiresAt', 'expires_at'),
            DateTime::make('createdAt')->sortable()->readOnly(),
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

    public function authorizable(): bool
    {
        return true;
    }
}

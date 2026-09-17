<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Invitations;

use App\Access\Role;
use App\Models\Invitation;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class InvitationSchema extends Schema
{
    public static string $model = Invitation::class;

    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('email'),
            Str::make('role')->serializeUsing(fn (mixed $value): mixed => $value instanceof Role ? $value->value : $value),
            Str::make('status'),
            DateTime::make('expiresAt', 'expires_at')->readOnly(),
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

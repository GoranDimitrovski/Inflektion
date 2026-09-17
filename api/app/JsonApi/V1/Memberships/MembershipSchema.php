<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Memberships;

use App\Access\Role;
use App\Models\Membership;
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

class MembershipSchema extends Schema
{
    public static string $model = Membership::class;

    /**
     * @return array<int, Field>
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('userId', 'user_id')->readOnly(),
            Str::make('userName')->extractUsing(fn (Membership $membership): string => $membership->user->name),
            Str::make('userEmail')->extractUsing(fn (Membership $membership): string => $membership->user->email),
            Str::make('role')->serializeUsing(fn (mixed $value): mixed => $value instanceof Role ? $value->value : $value),
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

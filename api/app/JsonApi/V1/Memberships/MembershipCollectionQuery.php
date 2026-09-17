<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Memberships;

use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class MembershipCollectionQuery extends ResourceQuery
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'fields' => [
                'nullable',
                'array',
                JsonApiRule::fieldSets(),
            ],
            'filter' => [
                'nullable',
                'array',
                JsonApiRule::filter(),
            ],
            'include' => [
                'nullable',
                'string',
                JsonApiRule::includePaths(),
            ],
            'page' => [
                'nullable',
                'array',
                JsonApiRule::page(),
            ],
            'sort' => [
                'nullable',
                'string',
                JsonApiRule::sort(),
            ],
            'withCount' => [
                'nullable',
                'string',
                JsonApiRule::countable(),
            ],
        ];
    }

    /**
     * @return array{number: int}|array<string, mixed>
     */
    public function page(): ?array
    {
        return parent::page() ?? ['number' => 1];
    }
}

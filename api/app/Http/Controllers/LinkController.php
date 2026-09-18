<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tracking\CreateLink;
use App\Actions\Tracking\UpdateLinkStatus;
use App\JsonApi\V1\Links\LinkRequest;
use App\Models\Link;
use App\Models\Program;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchMany;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Store as StoreAction;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Update as UpdateAction;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;

final class LinkController
{
    use FetchMany {
        index as private packageIndex;
    }
    use StoreAction {
        store as private packageStore;
    }
    use UpdateAction {
        update as private packageUpdate;
    }

    /**
     * @response array{data: list<array{type: string, id: string, attributes: array{programId: int, destinationUrl: string, token: string, redirectUrl: string, status: string, personalizationStrategy: string|null, createdAt: string, updatedAt: string}, links: array{self: string}}>, links: array{first: string, last: string, prev: string|null, next: string|null}, meta: array{page: array{currentPage: int, from: int|null, lastPage: int, perPage: int, to: int|null, total: int}}, jsonapi: array{version: string}}
     */
    #[QueryParameter(
        name: 'filter[programId]',
        description: 'Only return links belonging to this program.',
        required: false,
        type: 'string',
    )]
    public function index(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageIndex($route, $store);
    }

    /**
     * @response array{data: array{type: string, id: string, attributes: array{programId: int, destinationUrl: string, token: string, redirectUrl: string, status: string, personalizationStrategy: string|null, createdAt: string, updatedAt: string}, links: array{self: string}}, jsonapi: array{version: string}}
     */
    #[BodyParameter(
        name: 'data',
        description: 'The JSON:API resource object to create.',
        required: true,
        type: 'array{type: string, attributes: array{programId: int, destinationUrl: string, personalizationStrategy?: string}}',
    )]
    public function store(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageStore($route, $store);
    }

    /**
     * @response array{data: array{type: string, id: string, attributes: array{programId: int, destinationUrl: string, token: string, redirectUrl: string, status: string, personalizationStrategy: string|null, createdAt: string, updatedAt: string}, links: array{self: string}}, jsonapi: array{version: string}}
     */
    #[BodyParameter(
        name: 'data',
        description: 'The JSON:API resource object to update.',
        required: true,
        type: 'array{type: string, id: string, attributes: array{status: string}}',
    )]
    public function update(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageUpdate($route, $store);
    }

    public function creating(LinkRequest $request, ResourceQuery $query): DataResponse
    {
        $validated = $request->validated();

        $program = Program::query()->findOrFail($validated['programId']);
        $actor = $request->user();

        $link = app(CreateLink::class)->handle(
            $program,
            $validated['destinationUrl'],
            $validated['personalizationStrategy'] ?? null,
            $actor,
        );

        return DataResponse::make($link)
            ->withQueryParameters($query)
            ->didCreate();
    }

    public function updating(Link $link, LinkRequest $request, ResourceQuery $query): DataResponse
    {
        $actor = $request->user();

        $updated = app(UpdateLinkStatus::class)->handle($link, $request->validated()['status'], $actor);

        return DataResponse::make($updated)->withQueryParameters($query);
    }
}

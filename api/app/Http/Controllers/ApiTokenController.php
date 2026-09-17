<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Access\ApiToken;
use App\Access\AuditEntry;
use App\Access\TenantContext;
use App\Actions\Access\IssueApiToken;
use App\JsonApi\V1\ApiTokens\ApiTokenRequest;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Destroy;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchMany;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Store as StoreAction;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LogicException;

final class ApiTokenController
{
    use Destroy;
    use FetchMany {
        index as private packageIndex;
    }
    use StoreAction {
        store as private packageStore;
    }

    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    /**
     * @response array{data: list<array{type: string, id: string, attributes: array{name: string, abilities: list<string>, lastUsedAt: string|null, expiresAt: string|null, createdAt: string}, links: array{self: string}}>, links: array{first: string, last: string, prev: string|null, next: string|null}, meta: array{page: array{currentPage: int, from: int|null, lastPage: int, perPage: int, to: int|null, total: int}}, jsonapi: array{version: string}}
     */
    public function index(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageIndex($route, $store);
    }

    /**
     * @response array{data: array{type: string, id: string, attributes: array{name: string, abilities: list<string>, lastUsedAt: string|null, expiresAt: string|null, createdAt: string}, links: array{self: string}}, meta: array{plainTextToken: string}, jsonapi: array{version: string}}
     */
    #[BodyParameter(
        name: 'data',
        description: 'The JSON:API resource object to create.',
        required: true,
        type: 'array{type: string, attributes: array{name: string, abilities: list<string>, expiresAt?: string}}',
    )]
    public function store(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageStore($route, $store);
    }

    public function creating(ApiTokenRequest $request, ResourceQuery $query): Responsable
    {

        $actor = $request->user();

        $validated = $request->validated();
        $expiresAt = isset($validated['expiresAt']) ? Carbon::parse($validated['expiresAt']) : null;

        try {
            $newAccessToken = app(IssueApiToken::class)->handle(
                $this->tenant->account(),
                $actor,
                $this->tenant->role(),
                $validated['name'],
                $validated['abilities'],
                $expiresAt,
            );
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['abilities' => $e->getMessage()]);
        }

        return DataResponse::make($newAccessToken->accessToken)
            ->withQueryParameters($query)
            ->withMeta(['plainTextToken' => $newAccessToken->plainTextToken])
            ->didCreate();
    }

    public function deleting(ApiToken $token, Request $request): Response
    {

        $actor = $request->user();

        AuditEntry::record($token->account_id, $actor->id, 'access.token_revoked', $token, ['name' => $token->name], now());

        $token->delete();

        return response()->noContent();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Access\Role;
use App\Access\TenantContext;
use App\Actions\Access\ChangeRole;
use App\Actions\Access\RevokeAccess;
use App\JsonApi\V1\Memberships\MembershipRequest;
use App\Models\Membership;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Destroy;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchMany;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Update as UpdateAction;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LogicException;

final class MembershipController
{
    use Destroy;
    use FetchMany {
        index as private packageIndex;
    }
    use UpdateAction {
        update as private packageUpdate;
    }

    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    /**
     * @response array{data: list<array{type: string, id: string, attributes: array{userId: int, userName: string, userEmail: string, role: string, createdAt: string}, links: array{self: string}}>, links: array{first: string, last: string, prev: string|null, next: string|null}, meta: array{page: array{currentPage: int, from: int|null, lastPage: int, perPage: int, to: int|null, total: int}}, jsonapi: array{version: string}}
     */
    public function index(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageIndex($route, $store);
    }

    /**
     * @response array{data: array{type: string, id: string, attributes: array{userId: int, userName: string, userEmail: string, role: string, createdAt: string}, links: array{self: string}}, jsonapi: array{version: string}}
     */
    #[BodyParameter(
        name: 'data',
        description: 'The JSON:API resource object to update.',
        required: true,
        type: 'array{type: string, id: string, attributes: array{role: string}}',
    )]
    public function update(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageUpdate($route, $store);
    }

    public function updating(Membership $membership, MembershipRequest $request, ResourceQuery $query): Responsable
    {

        $actor = $request->user();

        try {
            $updated = app(ChangeRole::class)->handle(
                $membership,
                Role::from($request->validated()['role']),
                $actor,
                $this->tenant->role(),
            );
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['role' => $e->getMessage()]);
        }

        return DataResponse::make($updated)->withQueryParameters($query);
    }

    public function deleting(Membership $membership, Request $request): Response
    {

        $actor = $request->user();

        try {
            app(RevokeAccess::class)->handle($membership, $actor, $this->tenant->role());
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['role' => $e->getMessage()]);
        }

        return response()->noContent();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Access\AuditEntry;
use App\Access\Role;
use App\Access\TenantContext;
use App\Actions\Access\InviteUser;
use App\JsonApi\V1\Invitations\InvitationRequest;
use App\Models\Invitation;
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
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Store as StoreAction;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LogicException;

final class InvitationController
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

    public function index(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageIndex($route, $store);
    }

    #[BodyParameter(
        name: 'data',
        description: 'The JSON:API resource object to create.',
        required: true,
        type: 'array{type: string, attributes: array{email: string, role: string}}',
    )]
    public function store(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageStore($route, $store);
    }

    public function creating(InvitationRequest $request, ResourceQuery $query): Responsable
    {

        $inviter = $request->user();

        $validated = $request->validated();

        try {
            $invitation = app(InviteUser::class)->handle(
                $this->tenant->account(),
                $inviter,
                $this->tenant->role(),
                $validated['email'],
                Role::from($validated['role']),
            );
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['role' => $e->getMessage()]);
        }

        return DataResponse::make($invitation)
            ->withQueryParameters($query)
            ->didCreate();
    }

    public function deleting(Invitation $invitation, Request $request): Response
    {
        try {
            $invitation->revoke();
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        $actor = $request->user();
        AuditEntry::record($invitation->account_id, $actor->id, 'access.invitation_revoked', $invitation, [], now());

        return response()->noContent();
    }
}

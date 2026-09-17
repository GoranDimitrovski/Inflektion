<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Access\TenantContext;
use App\Actions\Access\RevokeAccess;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use LogicException;

final class LeaveAccountController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {}

    public function __invoke(Request $request): Response
    {

        $user = $request->user();

        $membership = Membership::query()
            ->where('account_id', $this->tenant->account()->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            app(RevokeAccess::class)->handle($membership, $user, $this->tenant->role());
        } catch (LogicException $e) {
            throw ValidationException::withMessages(['account' => $e->getMessage()]);
        }

        return response()->noContent();
    }
}

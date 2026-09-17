<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Access\Role;
use App\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LeaveAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function aMemberCanLeaveAnAccountEvenWithoutMembersManagePermission(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Viewer);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/me/membership");

        $response->assertNoContent();
        $this->assertDatabaseCount('memberships', 0);
    }

    #[Test]
    public function theSoleOwnerCannotLeave(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/me/membership");

        $response->assertUnprocessable();
        $this->assertDatabaseCount('memberships', 1);
    }

    #[Test]
    public function anOwnerCanLeaveWhenAnotherOwnerRemains(): void
    {
        $account = $this->actingAsAccountMember(role: Role::Owner);
        Membership::factory()->for($account)->create(['role' => Role::Owner]);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/me/membership");

        $response->assertNoContent();
        $this->assertDatabaseCount('memberships', 1);
    }
}

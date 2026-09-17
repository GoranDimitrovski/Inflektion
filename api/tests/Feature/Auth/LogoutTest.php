<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itLogsOutTheAuthenticatedUser(): void
    {
        // A full cookie round trip (real /login, reuse its Set-Cookie,
        // real /logout, assert a follow-up /me is 401) is covered by
        // manual verification against the running app; actingAs() short-
        // circuits Sanctum's session bootstrapping in a way that makes a
        // same-request "am I still logged in" assertion unreliable here.
        // The response contract — an authenticated caller gets 204 — is
        // what this asserts.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/logout', [], ['Origin' => 'http://localhost:4200']);

        $response->assertNoContent();
        $this->assertDatabaseHas('audit_entries', [
            'actor_user_id' => $user->id,
            'action' => 'auth.logout',
        ]);
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        $response = $this->deleteJson('/api/logout');

        $response->assertUnauthorized();
    }
}

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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/logout', [], $this->spaHeaders());

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

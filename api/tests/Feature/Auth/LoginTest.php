<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itLogsInWithValidCredentialsAndEstablishesASession(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ada@example.test',
            'password' => 'correct-password',
        ], $this->spaHeaders());

        $response->assertNoContent();
        $this->assertAuthenticated();
        $this->assertDatabaseHas('audit_entries', [
            'actor_user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    #[Test]
    public function itRejectsInvalidCredentials(): void
    {
        User::factory()->create([
            'email' => 'ada@example.test',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ada@example.test',
            'password' => 'wrong-password',
        ], $this->spaHeaders());

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_entries', [
            'actor_user_id' => null,
            'action' => 'auth.login_failed',
        ]);
    }

    #[Test]
    public function itRequiresAnEmailAndPassword(): void
    {
        $response = $this->postJson('/api/login', [], $this->spaHeaders());

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}

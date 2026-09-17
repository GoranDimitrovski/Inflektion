<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itSendsAResetLinkForARegisteredEmail(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.test']);

        $response = $this->postJson('/api/forgot-password', ['email' => 'ada@example.test']);

        $response->assertNoContent();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function itRespondsTheSameForAnUnregisteredEmailSoItCannotBeUsedToEnumerateAccounts(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.test']);

        $response->assertNoContent();
        Notification::assertNothingSent();
    }

    #[Test]
    public function itResetsThePasswordWithAValidTokenAndRevokesExistingApiTokens(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'ada@example.test',
            'password' => Hash::make('old-password'),
        ]);
        $user->createToken('test-token');

        $this->postJson('/api/forgot-password', ['email' => 'ada@example.test']);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'ada@example.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertNoContent();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('audit_entries', [
            'actor_user_id' => $user->id,
            'action' => 'auth.password_reset',
        ]);
    }

    #[Test]
    public function itRejectsAnInvalidToken(): void
    {
        User::factory()->create(['email' => 'ada@example.test']);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'ada@example.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertUnprocessable();
    }
}

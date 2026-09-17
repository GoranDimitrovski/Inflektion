<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

final class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGIN = ['Origin' => 'http://localhost:4200'];

    #[Test]
    public function loggingInWithConfirmedTwoFactorDoesNotEstablishASessionYet(): void
    {
        $user = $this->createUserWithConfirmedTwoFactor();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ], self::ORIGIN);

        $response->assertOk();
        $response->assertJsonPath('data.twoFactorRequired', true);
        $this->assertGuest();
    }

    #[Test]
    public function theChallengeCompletesLoginWithAValidCode(): void
    {
        $user = $this->createUserWithConfirmedTwoFactor();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], self::ORIGIN);

        $code = app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret));
        $response = $this->postJson('/api/two-factor-challenge', ['code' => $code], self::ORIGIN);

        $response->assertNoContent();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_entries', [
            'actor_user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    #[Test]
    public function theChallengeRejectsAnInvalidCode(): void
    {
        $user = $this->createUserWithConfirmedTwoFactor();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], self::ORIGIN);

        $response = $this->postJson('/api/two-factor-challenge', ['code' => '000000'], self::ORIGIN);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    #[Test]
    public function theChallengeAcceptsARecoveryCodeOnceAndRejectsItOnReuse(): void
    {
        $user = $this->createUserWithConfirmedTwoFactor();
        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], self::ORIGIN);
        $firstAttempt = $this->postJson('/api/two-factor-challenge', ['recovery_code' => $recoveryCode], self::ORIGIN);
        $firstAttempt->assertNoContent();

        $this->deleteJson('/api/logout', [], self::ORIGIN);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], self::ORIGIN);
        $secondAttempt = $this->postJson('/api/two-factor-challenge', ['recovery_code' => $recoveryCode], self::ORIGIN);

        $secondAttempt->assertUnprocessable();
    }

    #[Test]
    public function theChallengeFailsWithoutAPriorLoginAttempt(): void
    {
        $response = $this->postJson('/api/two-factor-challenge', ['code' => '000000'], self::ORIGIN);

        $response->assertUnprocessable();
    }

    /**
     * Set up entirely through the Action layer plus a direct forceFill, not
     * HTTP or Fortify's own ConfirmTwoFactorAuthentication action — this
     * test is about the login/challenge flow, not enable/confirm (covered
     * in TwoFactorAuthenticationTest). Going through the real confirm
     * action here would verify a code and cache it against replay for the
     * rest of that TOTP time-step (Google2FA's own anti-replay protection),
     * and since this setup and the test's own first challenge attempt both
     * run within the same time-step, the challenge's code would collide
     * with the one already spent confirming — a real "code reused" rejection,
     * not a bug in the login flow being tested. A prior real login+logout
     * cycle just to reach this precondition would separately entangle this
     * test's own session with that setup's, which is exactly what
     * LogoutTest's own comment already flags as unreliable to assert
     * against.
     */
    private function createUserWithConfirmedTwoFactor(): User
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user->fresh();
    }
}

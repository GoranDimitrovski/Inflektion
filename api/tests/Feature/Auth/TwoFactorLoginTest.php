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

    #[Test]
    public function loggingInWithConfirmedTwoFactorDoesNotEstablishASessionYet(): void
    {
        $user = $this->createUserWithConfirmedTwoFactor();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ], $this->spaHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.twoFactorRequired', true);
        $this->assertGuest();
    }

    #[Test]
    public function theChallengeCompletesLoginWithAValidCode(): void
    {
        $user = $this->createUserWithConfirmedTwoFactor();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], $this->spaHeaders());

        $code = app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret));
        $response = $this->postJson('/api/two-factor-challenge', ['code' => $code], $this->spaHeaders());

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
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], $this->spaHeaders());

        $response = $this->postJson('/api/two-factor-challenge', ['code' => '000000'], $this->spaHeaders());

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    #[Test]
    public function theChallengeAcceptsARecoveryCodeOnceAndRejectsItOnReuse(): void
    {
        $user = $this->createUserWithConfirmedTwoFactor();
        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], $this->spaHeaders());
        $firstAttempt = $this->postJson('/api/two-factor-challenge', ['recovery_code' => $recoveryCode], $this->spaHeaders());
        $firstAttempt->assertNoContent();

        $this->deleteJson('/api/logout', [], $this->spaHeaders());

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'], $this->spaHeaders());
        $secondAttempt = $this->postJson('/api/two-factor-challenge', ['recovery_code' => $recoveryCode], $this->spaHeaders());

        $secondAttempt->assertUnprocessable();
    }

    #[Test]
    public function theChallengeFailsWithoutAPriorLoginAttempt(): void
    {
        $response = $this->postJson('/api/two-factor-challenge', ['code' => '000000'], $this->spaHeaders());

        $response->assertUnprocessable();
    }

    private function createUserWithConfirmedTwoFactor(): User
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user->fresh();
    }
}

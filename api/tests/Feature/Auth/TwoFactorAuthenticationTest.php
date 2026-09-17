<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

final class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itEnablesConfirmsAndThenAllowsDisabling(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $enableResponse = $this->postJson('/api/two-factor-authentication');
        $enableResponse->assertNoContent();
        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('audit_entries', ['actor_user_id' => $user->id, 'action' => 'access.2fa_enabled']);

        $code = $this->currentOtpFor($user->fresh());
        $confirmResponse = $this->postJson('/api/confirmed-two-factor-authentication', ['code' => $code]);
        $confirmResponse->assertNoContent();
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('audit_entries', ['actor_user_id' => $user->id, 'action' => 'access.2fa_confirmed']);

        $disableResponse = $this->deleteJson('/api/two-factor-authentication');
        $disableResponse->assertNoContent();
        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertDatabaseHas('audit_entries', ['actor_user_id' => $user->id, 'action' => 'access.2fa_disabled']);
    }

    #[Test]
    public function confirmingWithAnInvalidCodeFails(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->postJson('/api/two-factor-authentication');

        $response = $this->postJson('/api/confirmed-two-factor-authentication', ['code' => '000000']);

        $response->assertUnprocessable();
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    #[Test]
    public function anOwnerCannotDisableTwoFactorAuthentication(): void
    {
        $user = User::factory()->create();
        Membership::factory()->create(['account_id' => Account::factory()->create()->id, 'user_id' => $user->id, 'role' => Role::Owner]);
        $this->actingAs($user);
        $this->postJson('/api/two-factor-authentication');
        $this->postJson('/api/confirmed-two-factor-authentication', ['code' => $this->currentOtpFor($user->fresh())]);

        $response = $this->deleteJson('/api/two-factor-authentication');

        $response->assertUnprocessable();
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    #[Test]
    public function aNonOwnerCanDisableTwoFactorAuthentication(): void
    {
        $user = User::factory()->create();
        Membership::factory()->create(['account_id' => Account::factory()->create()->id, 'user_id' => $user->id, 'role' => Role::Admin]);
        $this->actingAs($user);
        $this->postJson('/api/two-factor-authentication');
        $this->postJson('/api/confirmed-two-factor-authentication', ['code' => $this->currentOtpFor($user->fresh())]);

        $response = $this->deleteJson('/api/two-factor-authentication');

        $response->assertNoContent();
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    #[Test]
    public function regeneratingRecoveryCodesInvalidatesTheOldOnes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->postJson('/api/two-factor-authentication');
        $this->postJson('/api/confirmed-two-factor-authentication', ['code' => $this->currentOtpFor($user->fresh())]);

        $originalCodes = $this->getJson('/api/two-factor-recovery-codes')->json('data');

        $response = $this->postJson('/api/two-factor-recovery-codes');

        $response->assertOk();
        $newCodes = $response->json('data');
        $this->assertNotEquals($originalCodes, $newCodes);
        $this->assertDatabaseHas('audit_entries', ['actor_user_id' => $user->id, 'action' => 'access.2fa_recovery_codes_regenerated']);
    }

    private function currentOtpFor(User $user): string
    {
        return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
    }
}

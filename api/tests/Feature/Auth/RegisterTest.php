<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Access\Role;
use App\Models\Account;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRegistersAUserWithANewOwnerAccountAndEstablishesASession(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'correct-password',
            'password_confirmation' => 'correct-password',
            'accountName' => 'Acme Inc',
        ], $this->spaHeaders());

        $response->assertNoContent();
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ada@example.test')->firstOrFail();
        $account = Account::query()->where('name', 'Acme Inc')->firstOrFail();

        $this->assertDatabaseHas('memberships', [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'role' => Role::Owner->value,
        ]);
        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $user->id,
            'action' => 'access.account_registered',
        ]);
    }

    #[Test]
    public function itRejectsARegistrationForAnEmailThatIsAlreadyTaken(): void
    {
        User::factory()->create(['email' => 'ada@example.test']);

        $response = $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'correct-password',
            'password_confirmation' => 'correct-password',
            'accountName' => 'Acme Inc',
        ], $this->spaHeaders());

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->assertSame(0, Membership::query()->count());
    }

    #[Test]
    public function itRequiresAllFields(): void
    {
        $response = $this->postJson('/api/register', [], $this->spaHeaders());

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'email', 'password', 'accountName']);
    }

    #[Test]
    public function itRequiresAPasswordConfirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'correct-password',
            'password_confirmation' => 'does-not-match',
            'accountName' => 'Acme Inc',
        ], $this->spaHeaders());

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
        $this->assertGuest();
    }
}

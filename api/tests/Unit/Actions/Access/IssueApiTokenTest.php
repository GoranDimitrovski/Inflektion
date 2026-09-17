<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Access;

use App\Access\Role;
use App\Actions\Access\IssueApiToken;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IssueApiTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itIssuesATokenScopedToTheAccountAndAuditsIt(): void
    {
        $account = Account::factory()->create();
        $actor = User::factory()->create();

        $newAccessToken = app(IssueApiToken::class)->handle(
            $account,
            $actor,
            Role::Admin,
            'CI script',
            ['programs.read'],
            null,
        );

        $this->assertSame($account->id, $newAccessToken->accessToken->account_id);
        $this->assertSame(['programs.read'], $newAccessToken->accessToken->abilities);
        $this->assertNotEmpty($newAccessToken->plainTextToken);

        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $actor->id,
            'action' => 'access.token_issued',
            'subject_id' => $newAccessToken->accessToken->id,
        ]);
    }

    #[Test]
    public function itRefusesAnAbilityTheActorsRoleDoesNotHave(): void
    {
        $account = Account::factory()->create();
        $actor = User::factory()->create();

        $this->expectException(LogicException::class);

        try {
            app(IssueApiToken::class)->handle($account, $actor, Role::Viewer, 'Escalation attempt', ['programs.write'], null);
        } finally {
            $this->assertDatabaseCount('personal_access_tokens', 0);
        }
    }
}

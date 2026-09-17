<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Tracking;

use App\Actions\Tracking\CreateLink;
use App\Models\Account;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCreatesAnActiveLinkWithAGeneratedToken(): void
    {
        $account = Account::factory()->create();
        $program = Program::factory()->for($account)->create();
        $actor = User::factory()->create();

        $link = app(CreateLink::class)->handle($program, 'https://example.test/landing', null, $actor);

        $this->assertSame($account->id, $link->account_id);
        $this->assertSame($program->id, $link->program_id);
        $this->assertSame('https://example.test/landing', $link->destination_url);
        $this->assertSame('active', $link->status);
        $this->assertNotEmpty($link->token);
        $this->assertNull($link->personalization_strategy);
    }

    #[Test]
    public function itRecordsThePersonalizationStrategyWhenGiven(): void
    {
        $account = Account::factory()->create();
        $program = Program::factory()->for($account)->create();
        $actor = User::factory()->create();

        $link = app(CreateLink::class)->handle($program, 'https://example.test/landing', 'weighted', $actor);

        $this->assertSame('weighted', $link->personalization_strategy);
    }

    #[Test]
    public function itAuditsTheLinkCreation(): void
    {
        $account = Account::factory()->create();
        $program = Program::factory()->for($account)->create();
        $actor = User::factory()->create();

        $link = app(CreateLink::class)->handle($program, 'https://example.test/landing', null, $actor);

        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $actor->id,
            'action' => 'tracking.link_created',
            'subject_type' => $link->getMorphClass(),
            'subject_id' => $link->id,
        ]);
    }
}

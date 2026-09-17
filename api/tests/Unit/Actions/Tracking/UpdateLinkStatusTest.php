<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Tracking;

use App\Actions\Tracking\UpdateLinkStatus;
use App\Models\Account;
use App\Models\Link;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UpdateLinkStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itUpdatesTheStatus(): void
    {
        $account = Account::factory()->create();
        $link = Link::factory()->for(Program::factory()->for($account))->create(['status' => 'active']);
        $actor = User::factory()->create();

        $updated = app(UpdateLinkStatus::class)->handle($link, 'paused', $actor);

        $this->assertSame('paused', $updated->fresh()->status);
    }

    #[Test]
    public function itAuditsTheStatusChange(): void
    {
        $account = Account::factory()->create();
        $link = Link::factory()->for(Program::factory()->for($account))->create(['status' => 'active']);
        $actor = User::factory()->create();

        app(UpdateLinkStatus::class)->handle($link, 'paused', $actor);

        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $actor->id,
            'action' => 'tracking.link_status_changed',
            'subject_type' => $link->getMorphClass(),
            'subject_id' => $link->id,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Access;

use App\Access\AuditEntry;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuditEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itRecordsAnEntryWithTheGivenSubject(): void
    {
        $account = Account::factory()->create();

        $entry = AuditEntry::record($account->id, null, 'access.invitation_sent', $account, ['email' => 'a@example.com'], now());

        $this->assertSame($account->id, $entry->account_id);
        $this->assertSame('access.invitation_sent', $entry->action);
        $this->assertSame($account->getMorphClass(), $entry->subject_type);
        $this->assertSame($account->id, $entry->subject_id);
        $this->assertSame(['email' => 'a@example.com'], $entry->metadata);
    }

    #[Test]
    public function itAllowsANullSubjectAndActor(): void
    {
        $entry = AuditEntry::record(null, null, 'auth.login_failed', null, ['email' => 'a@example.com'], now());

        $this->assertNull($entry->account_id);
        $this->assertNull($entry->actor_user_id);
        $this->assertNull($entry->subject_type);
        $this->assertNull($entry->subject_id);
    }

    #[Test]
    public function itRefusesToUpdateAnEntry(): void
    {
        $entry = AuditEntry::record(null, null, 'auth.login', null, [], now());

        $this->expectException(LogicException::class);

        $entry->update(['action' => 'auth.logout']);
    }

    #[Test]
    public function itRefusesToDeleteAnEntry(): void
    {
        $entry = AuditEntry::record(null, null, 'auth.login', null, [], now());

        $this->expectException(LogicException::class);

        try {
            $entry->delete();
        } finally {
            $this->assertTrue(AuditEntry::query()->whereKey($entry->id)->exists());
        }
    }
}

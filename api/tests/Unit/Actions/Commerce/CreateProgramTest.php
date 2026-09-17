<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Commerce;

use App\Access\Role;
use App\Access\TenantContext;
use App\Actions\Commerce\CreateProgram;
use App\Actions\Commerce\CreateProgramData;
use App\Models\Account;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateProgramTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itReliesOnTheDbUniqueConstraintNotAPreCheckForSlugUniqueness(): void
    {
        app(TenantContext::class)->set(Account::factory()->create(), Role::Member);

        $action = app(CreateProgram::class);
        $data = new CreateProgramData(name: 'Acme', slug: 'acme');
        $actor = User::factory()->create();

        $action->handle($data, $actor);

        $this->expectException(UniqueConstraintViolationException::class);

        try {
            $action->handle($data, $actor);
        } finally {
            $this->assertSame(1, Program::query()->where('slug', 'acme')->count());
        }
    }

    #[Test]
    public function itAuditsTheCommissionStrategyAssignmentWhenOneIsGiven(): void
    {
        $account = Account::factory()->create();
        app(TenantContext::class)->set($account, Role::Member);
        $actor = User::factory()->create();

        $data = new CreateProgramData(
            name: 'Acme',
            slug: 'acme-percentage',
            commissionStrategy: 'percentage',
            commissionRate: '0.1000',
        );

        $program = app(CreateProgram::class)->handle($data, $actor);

        $this->assertDatabaseHas('audit_entries', [
            'account_id' => $account->id,
            'actor_user_id' => $actor->id,
            'action' => 'commerce.commission_strategy_assigned',
            'subject_type' => $program->getMorphClass(),
            'subject_id' => $program->id,
        ]);
    }

    #[Test]
    public function itDoesNotAuditWhenNoCommissionStrategyIsGiven(): void
    {
        $account = Account::factory()->create();
        app(TenantContext::class)->set($account, Role::Member);
        $actor = User::factory()->create();

        app(CreateProgram::class)->handle(new CreateProgramData(name: 'Acme', slug: 'acme-no-strategy'), $actor);

        $this->assertDatabaseMissing('audit_entries', ['action' => 'commerce.commission_strategy_assigned']);
    }
}

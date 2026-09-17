<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Access\ApiToken;
use App\Access\Role;
use App\Access\TenantContext;
use App\Models\Account;
use App\Models\Click;
use App\Models\CommissionLedgerEntry;
use App\Models\Conversion;
use App\Models\Invitation;
use App\Models\Link;
use App\Models\PayoutBatch;
use App\Models\Program;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<class-string, callable(Account): object>
     */
    private function tenantOwnedModels(): array
    {
        return [
            Program::class => fn (Account $account): Program => Program::factory()->for($account)->create(),

            Link::class => fn (Account $account): Link => Link::factory()
                ->for(Program::factory()->for($account))
                ->create(),

            Click::class => fn (Account $account): Click => Click::create([
                'account_id' => $account->id,
                'link_id' => Link::factory()->for(Program::factory()->for($account))->create()->id,
                'occurred_at' => now(),
            ]),

            Conversion::class => fn (Account $account): Conversion => Conversion::create([
                'account_id' => $account->id,
                'vendor' => 'demo-store',
                'external_id' => 'tenant-isolation-'.Str::random(10),
                'amount' => Money::of(1000, 'USD'),
                'status' => 'attributed',
                'attributed_program_id' => Program::factory()->for($account)->create()->id,
            ]),

            CommissionLedgerEntry::class => function (Account $account): CommissionLedgerEntry {
                $program = Program::factory()->for($account)->create();
                $conversion = Conversion::create([
                    'account_id' => $account->id,
                    'vendor' => 'demo-store',
                    'external_id' => 'tenant-isolation-'.Str::random(10),
                    'amount' => Money::of(1000, 'USD'),
                    'status' => 'attributed',
                    'attributed_program_id' => $program->id,
                ]);

                return CommissionLedgerEntry::create([
                    'account_id' => $account->id,
                    'conversion_id' => $conversion->id,
                    'program_id' => $program->id,
                    'amount' => Money::of(100, 'USD'),
                    'type' => 'commission',
                    'created_at' => now(),
                ]);
            },

            PayoutBatch::class => fn (Account $account): PayoutBatch => PayoutBatch::create([
                'account_id' => $account->id,
                'status' => PayoutBatch::STATUS_OPEN,
                'opened_at' => now(),
            ]),

            Invitation::class => fn (Account $account): Invitation => Invitation::factory()->for($account)->create(),

            ApiToken::class => fn (Account $account): ApiToken => ApiToken::create([
                'tokenable_type' => User::class,
                'tokenable_id' => User::factory()->create()->id,
                'name' => 'tenant-isolation-'.Str::random(10),
                'token' => hash('sha256', Str::random(40)),
                'abilities' => ['programs.read'],
                'account_id' => $account->id,
            ]),
        ];
    }

    #[Test]
    public function tenantOwnedModelsAreInvisibleAcrossAccounts(): void
    {
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();

        foreach ($this->tenantOwnedModels() as $modelClass => $seed) {
            $this->app->forgetInstance(TenantContext::class);

            $seed($accountA);
            $rowInB = $seed($accountB);

            app(TenantContext::class)->set($accountA, Role::Member);

            $visibleIds = $modelClass::query()->pluck('id')->all();

            $this->assertNotContains(
                $rowInB->id,
                $visibleIds,
                "{$modelClass} leaked a row from another account across the tenant scope.",
            );
        }
    }
}

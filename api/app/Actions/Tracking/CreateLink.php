<?php

declare(strict_types=1);

namespace App\Actions\Tracking;

use App\Access\AuditEntry;
use App\Models\Link;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateLink
{
    public function handle(
        Program $program,
        string $destinationUrl,
        ?string $personalizationStrategy,
        User $actor,
    ): Link {
        return DB::transaction(function () use ($program, $destinationUrl, $personalizationStrategy, $actor): Link {
            $link = Link::create([
                'account_id' => $program->account_id,
                'program_id' => $program->id,
                'destination_url' => $destinationUrl,
                'token' => Str::random(10),
                'status' => 'active',
                'personalization_strategy' => $personalizationStrategy,
            ]);

            AuditEntry::record($program->account_id, $actor->id, 'tracking.link_created', $link, [], now());

            return $link;
        });
    }
}

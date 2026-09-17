<?php

declare(strict_types=1);

namespace App\Actions\Attribution;

use App\Events\ConversionAttributed;
use App\Models\Click;
use App\Models\Conversion;
use Illuminate\Support\Facades\DB;

final class AttributeConversion
{
    public function handle(Conversion $conversion): void
    {
        if ($conversion->click_id === null) {
            return;
        }

        $attributed = DB::transaction(function () use ($conversion): bool {
            $link = Click::query()
                ->whereKey($conversion->click_id)
                ->with('link')
                ->first()
                ?->link;

            if ($link === null) {
                return false;
            }

            $conversion->update([
                'account_id' => $link->account_id,
                'attributed_program_id' => $link->program_id,
                'status' => 'attributed',
            ]);

            return true;
        });

        if ($attributed) {
            event(new ConversionAttributed($conversion));
        }
    }
}

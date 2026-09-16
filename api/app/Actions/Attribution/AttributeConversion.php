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
            $programId = Click::query()
                ->whereKey($conversion->click_id)
                ->with('link')
                ->first()
                ?->link
                ?->program_id;

            if ($programId === null) {
                return false;
            }

            $conversion->update([
                'attributed_program_id' => $programId,
                'status' => 'attributed',
            ]);

            return true;
        });

        if ($attributed) {
            event(new ConversionAttributed($conversion));
        }
    }
}

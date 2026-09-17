<?php

declare(strict_types=1);

namespace App\Actions\Attribution;

use App\Events\ConversionRecorded;
use App\Integrations\Storefronts\StorefrontAdapterRegistry;
use App\Models\Click;
use App\Models\Conversion;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class AcceptPostback
{
    public function __construct(
        private readonly StorefrontAdapterRegistry $adapters,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $vendor, array $payload): Conversion
    {
        $adapter = $this->adapters->resolve($vendor);
        $data = $adapter->parsePostback($payload);

        try {
            $conversion = DB::transaction(function () use ($vendor, $data): Conversion {

                $clickId = $data->clickId !== null && ctype_digit($data->clickId)
                    ? Click::query()->whereKey($data->clickId)->value('id')
                    : null;

                return Conversion::create([
                    'vendor' => $vendor,
                    'external_id' => $data->externalId,
                    'click_id' => $clickId,
                    'amount' => $data->amount,
                    'status' => 'recorded',
                ]);
            });
        } catch (UniqueConstraintViolationException) {

            return Conversion::query()
                ->where('vendor', $vendor)
                ->where('external_id', $data->externalId)
                ->firstOrFail();
        }

        event(new ConversionRecorded($conversion));

        return $conversion;
    }
}

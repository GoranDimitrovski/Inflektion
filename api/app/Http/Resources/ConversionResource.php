<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Conversion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Conversion $resource
 */
final class ConversionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'vendor' => $this->resource->vendor,
            'external_id' => $this->resource->external_id,
            'status' => $this->resource->status,
        ];
    }
}

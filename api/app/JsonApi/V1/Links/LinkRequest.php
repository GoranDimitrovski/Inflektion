<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Links;

use App\Access\TenantContext;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LinkRequest extends ResourceRequest
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        if ($this->isUpdating()) {
            return [
                'status' => ['required', 'string', Rule::in(['active', 'paused'])],
            ];
        }

        return [
            'programId' => [
                'required',
                'integer',
                Rule::exists('programs', 'id')->where('account_id', $this->tenant->account()->id),
            ],
            'destinationUrl' => ['required', 'url', 'max:2048'],
            'personalizationStrategy' => ['nullable', 'string', 'max:255'],
        ];
    }
}

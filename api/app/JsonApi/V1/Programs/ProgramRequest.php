<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Programs;

use App\Commissions\CommissionStrategyRegistry;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class ProgramRequest extends ResourceRequest
{
    public function __construct(
        private readonly CommissionStrategyRegistry $commissionStrategies,
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('programs', 'slug')->ignore($this->model()?->getKey()),
            ],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'paused'])],
            'commissionStrategy' => ['nullable', 'string', Rule::in($this->commissionStrategies->knownIdentifiers())],
            'commissionRate' => ['nullable', 'numeric', 'between:0,1', 'required_if:commissionStrategy,percentage'],
            'commissionFlatAmountMinorUnits' => ['nullable', 'integer', 'min:0', 'required_if:commissionStrategy,flat'],
            'commissionFlatAmountCurrency' => ['nullable', 'string', 'size:3', 'required_if:commissionStrategy,flat'],
        ];
    }
}

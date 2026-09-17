<?php

declare(strict_types=1);

namespace App\JsonApi\V1\ApiTokens;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class ApiTokenRequest extends ResourceRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string'],
            'expiresAt' => ['nullable', 'date'],
        ];
    }
}

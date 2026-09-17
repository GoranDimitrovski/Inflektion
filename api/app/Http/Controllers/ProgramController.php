<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Commerce\CreateProgram;
use App\Actions\Commerce\CreateProgramData;
use App\JsonApi\V1\Programs\ProgramRequest;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchMany;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Store as StoreAction;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;

final class ProgramController
{
    use FetchMany {
        index as private packageIndex;
    }
    use StoreAction {
        store as private packageStore;
    }

    public function index(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageIndex($route, $store);
    }

    #[BodyParameter(
        name: 'data',
        description: 'The JSON:API resource object to create.',
        required: true,
        type: 'array{type: string, attributes: array{name: string, slug: string, status?: string, commissionStrategy?: string, commissionRate?: string, commissionFlatAmountMinorUnits?: int, commissionFlatAmountCurrency?: string}}',
    )]
    public function store(Route $route, StoreContract $store): Responsable|Response
    {
        return $this->packageStore($route, $store);
    }

    public function creating(ProgramRequest $request, ResourceQuery $query): DataResponse
    {

        $actor = $request->user();

        try {
            $program = app(CreateProgram::class)->handle(CreateProgramData::fromArray($request->validated()), $actor);
        } catch (UniqueConstraintViolationException $e) {
            Log::info('Program creation lost a slug uniqueness race past the pre-check', [
                'slug' => $request->validated()['slug'] ?? null,
                'exception' => $e::class,
            ]);

            throw ValidationException::withMessages([
                'slug' => 'The slug has already been taken.',
            ]);
        }

        return DataResponse::make($program)
            ->withQueryParameters($query)
            ->didCreate();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Attribution;

use App\Actions\Attribution\AcceptPostback;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attribution\PostbackRequest;
use App\Http\Resources\ConversionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final class PostbackController extends Controller
{
    public function __invoke(PostbackRequest $request, string $vendor, AcceptPostback $acceptPostback): JsonResponse
    {
        try {
            $conversion = $acceptPostback->handle($vendor, $request->all());
        } catch (InvalidArgumentException $e) {
            Log::warning('Postback rejected', [
                'vendor' => $vendor,
                'exception' => $e::class,
                'reason' => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        return ConversionResource::make($conversion)
            ->response()
            ->setStatusCode(202);
    }
}

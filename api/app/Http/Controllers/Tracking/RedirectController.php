<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tracking;

use App\Actions\Tracking\RecordClick;
use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Personalization\PersonalizationContext;
use App\Personalization\PersonalizationStrategyRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RedirectController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        RecordClick $recordClick,
        PersonalizationStrategyRegistry $personalization,
    ): RedirectResponse {
        $link = Link::query()->active()->where('token', $token)->firstOrFail();

        $click = $recordClick->handle($link, $request->ip(), $request->userAgent());

        $variant = $personalization->decide(
            $link->personalization_strategy,
            $link,
            new PersonalizationContext($click->occurred_at, $request->userAgent()),
        );

        $params = array_merge(['click_id' => (string) $click->id], $variant->queryParams);

        $separator = str_contains($link->destination_url, '?') ? '&' : '?';

        return redirect()->away($link->destination_url.$separator.http_build_query($params));
    }
}

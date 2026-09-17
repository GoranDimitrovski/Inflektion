<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Access\RegisterAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterAccount $registerAccount): Response
    {
        $user = $registerAccount->handle(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->string('accountName')->toString(),
        );

        $guard = Auth::guard('web');
        $guard->login($user);
        $request->session()->regenerate();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleOAuthService $googleOAuthService,
    ) {
    }

    public function create(): RedirectResponse
    {
        return $this->googleOAuthService->redirectToProvider();
    }

    public function store(): RedirectResponse
    {
        try {
            $user = $this->googleOAuthService->resolveUserFromCallback();

            Auth::login($user, true);

            return redirect()->intended(route('admin.dashboard'));
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google authentication failed. Please try again.']);
        }
    }
}

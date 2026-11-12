<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthService
{
    public function redirectToProvider(): RedirectResponse
    {
        /** @var RedirectResponse $response */
        $response = Socialite::driver('google')
            ->redirect();

        return $response;
    }

    public function resolveUserFromCallback(): User
    {
        $googleUser = Socialite::driver('google')->user();
        $email = $googleUser->getEmail();

        if (! $email) {
            throw ValidationException::withMessages([
                'email' => 'Google account did not provide an email address.',
            ]);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
            ]);

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            return $user;
        }

        $user->forceFill([
            'google_id' => $user->google_id ?: $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        return $user;
    }
}

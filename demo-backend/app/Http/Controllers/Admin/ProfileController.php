<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        $qrCodeUrl    = null;
        $recoveryCodes = null;

        if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
            $qrCodeUrl = $user->twoFactorQrCodeUrl();
        }

        if ($user->two_factor_confirmed_at) {
            $recoveryCodes = $user->recoveryCodes();
        }

        return view('admin.profile.index', compact('user', 'qrCodeUrl', 'recoveryCodes'));
    }

    public function updateInfo(Request $request, UpdateUserProfileInformation $action)
    {
        $action->update(Auth::user(), $request->all());

        return back()->with('info_success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request, UpdateUserPassword $action)
    {
        $action->update(Auth::user(), $request->all());

        return back()->with('password_success', 'Password updated successfully.');
    }

    public function twoFactorEnable(EnableTwoFactorAuthentication $action)
    {
        $action(Auth::user());

        return back()->with('2fa_status', 'scan');
    }

    public function twoFactorConfirm(Request $request, ConfirmTwoFactorAuthentication $action)
    {
        $request->validate(['code' => ['required', 'string']]);

        $action(Auth::user(), $request->code);

        return back()->with('2fa_status', 'confirmed');
    }

    public function twoFactorDisable(DisableTwoFactorAuthentication $action)
    {
        $action(Auth::user());

        return back()->with('2fa_status', 'disabled');
    }

    public function twoFactorRegenerateCodes(GenerateNewRecoveryCodes $action)
    {
        $action(Auth::user());

        return back()->with('2fa_status', 'codes_regenerated');
    }
}

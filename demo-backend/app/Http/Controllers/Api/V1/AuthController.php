<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        $data = $req->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers(), 'confirmed'],
            'locale'   => ['nullable','string','max:10'],
        ]);

        $tokenName = $req->header('X-Device-Name', 'storefront');

        /** @var \App\Models\User $user */
        $user = DB::transaction(function () use ($data) {
            // rely on 'hashed' cast in User model (no manual Hash::make)
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'locale'   => $data['locale'] ?? config('app.locale'),
            ]);

            // Spatie Role: Customer (create if missing)
            if (class_exists(Role::class)) {
                $role = Role::firstOrCreate(['name' => 'Customer']);
                $user->assignRole($role);
            }

            return $user;
        });

        // Issue Sanctum token
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function login(Request $req)
    {
        $data = $req->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // (Optional) block if email not verified
        // if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail()) {
        //     return response()->json(['message' => 'Email not verified'], 403);
        // }

        $tokenName = $req->header('X-Device-Name', 'storefront');
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $req)
    {
        $req->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function logoutAll(Request $req)
    {
        $req->user()?->tokens()->delete();
        return response()->json(['message' => 'Logged out from all devices']);
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'google_avatar',
        'password',
        'locale',
        'preferences',
        'current_channel_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    public function manualRoles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function accessLevels()
    {
        return $this->belongsToMany(AccessLevel::class, 'user_access_level');
    }

    public function groups()
    {
        return $this->belongsToMany(\App\Models\UserGroup::class, 'user_group_user');
    }

    public function addresses() { return $this->hasMany(UserAddress::class); }

    public function defaultShippingAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default_shipping', true);
    }
    public function defaultBillingAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default_billing', true);
    }

    public function channels()
    {
        return $this->belongsToMany(Channel::class);
    }

    public function currentChannel()
    {
        return $this->belongsTo(Channel::class, 'current_channel_id');
    }

    /* ──────────────── Mail notification overrides ──────────────────── */

    /**
     * Use our custom template instead of Laravel's default
     * email-verification markdown email.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Use our custom template instead of Laravel's default
     * password-reset markdown email.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }


}

<?php

namespace App\Models;

use App\Services\OtpService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CUSTOMER = 'customer';

    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'role', 'status',
        'email_verified_at', 'mobile_verified_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ---- Relationships ----

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latestOfMany('expires_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_id');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'user_id', 'favorite_user_id')->withTimestamps();
    }

    // ---- Role / state helpers ----

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function currentPlan(): ?MembershipPlan
    {
        return $this->activeSubscription?->plan;
    }

    public function isPremium(): bool
    {
        return (bool) $this->activeSubscription;
    }

    public function hasApprovedProfile(): bool
    {
        return $this->profile?->approval_status === Profile::STATUS_APPROVED;
    }

    public function hasAcceptedInterestWith(User $other): bool
    {
        return Interest::where('status', 'accepted')
            ->where(fn ($q) => $q->where(['sender_id' => $this->id, 'receiver_id' => $other->id])
                ->orWhere(fn ($q) => $q->where(['sender_id' => $other->id, 'receiver_id' => $this->id])))
            ->exists();
    }

    /** Chat is allowed with a premium (chat-enabled) plan, or once an interest has been accepted. */
    public function canChatWith(User $other): bool
    {
        if ($this->id === $other->id || $other->isBlocked() || ! $other->hasApprovedProfile()) {
            return false;
        }

        return (bool) $this->currentPlan()?->can_chat || $this->hasAcceptedInterestWith($other);
    }

    public function hasFavorited(User $other): bool
    {
        return $this->favorites()->whereKey($other->id)->exists();
    }

    /** Send the email OTP instead of Laravel's default signed verification link. */
    public function sendEmailVerificationNotification(): void
    {
        app(OtpService::class)->send($this->email, OtpService::PURPOSE_VERIFY_EMAIL);
    }

    public function scopeCustomers($query)
    {
        return $query->where('role', self::ROLE_CUSTOMER);
    }
}

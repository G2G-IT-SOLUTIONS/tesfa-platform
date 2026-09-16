<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasRoles;
    use InteractsWithMedia;
    use HasSpatial;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'id_type',
        'id_number',
        'id_document_path',
        'id_verified',
        'id_verified_at',
        'id_verified_by',
        'role',
        'country',
        'city',
        'neighborhood',
        'gps_location',
        'diaspora_location',
        'preferred_currency',
        'timezone',
        'preferred_locale',
        'mfa_enabled',
        'mfa_secret',
        'last_login_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'id_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'gps_location' => Point::class,
        'mfa_enabled' => 'boolean',
        'id_verified' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function agentProperties()
    {
        return $this->hasMany(Property::class, 'agent_id');
    }

    public function agentProfile()
    {
        return $this->hasOne(AgentProfile::class);
    }

    public function escrows()
    {
        return $this->hasMany(Escrow::class, 'buyer_id');
    }

    public function sellerEscrows()
    {
        return $this->hasMany(Escrow::class, 'seller_id');
    }

    public function rentToOwnContracts()
    {
        return $this->hasMany(RentToOwnContract::class, 'buyer_id');
    }

    public function shortTermBookings()
    {
        return $this->hasMany(ShortTermBooking::class, 'guest_id');
    }

    public function creditScore()
    {
        return $this->hasOne(CreditScore::class);
    }

    public function creditScoreLogs()
    {
        return $this->hasMany(CreditScoreLog::class);
    }

    public function behaviorLogs()
    {
        return $this->hasMany(UserBehaviorLog::class);
    }

    public function behaviorSummaries()
    {
        return $this->hasMany(UserBehaviorSummary::class);
    }

    public function consents()
    {
        return $this->hasMany(UserConsent::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function dataSubjectRequests()
    {
        return $this->hasMany(DataSubjectRequest::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=078930&color=fff';
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getIsAgentAttribute(): bool
    {
        return in_array($this->role, ['agent_certified', 'agent_premier', 'agent_master']);
    }

    public function getIsAdminAttribute(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /*
    |--------------------------------------------------------------------------
    | Media Collections
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100);
            });

        $this->addMediaCollection('id_document')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function hasConsent(string $type): bool
    {
        return $this->consents()
            ->where('consent_type', $type)
            ->where('granted', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function getFinancingOptions(): array
    {
        return app(\App\Services\AI\CreditScoringService::class)
            ->getFinancingOptions($this);
    }
}
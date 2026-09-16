<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Property extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;
    use HasSlug;
    use HasSpatial;

    protected $fillable = [
        'owner_id',
        'agent_id',
        'title',
        'description',
        'type',
        'purpose',
        'price',
        'price_currency',
        'price_negotiable',
        'rent_to_own_enabled',
        'rent_to_own_down_payment_pct',
        'rent_to_own_monthly_payment',
        'rent_to_own_duration_months',
        'rent_to_own_total_cost',
        'short_term_enabled',
        'nightly_rate',
        'nightly_rate_currency',
        'min_nights',
        'max_nights',
        'cleaning_fee',
        'security_deposit',
        'bedrooms',
        'bathrooms',
        'total_rooms',
        'area_sqm',
        'floor_number',
        'total_floors',
        'year_built',
        'condition',
        'address',
        'neighborhood',
        'sub_city',
        'city',
        'region',
        'country',
        'location',
        'features',
        'amenities',
        'nearby',
        'photos',
        'videos',
        'virtual_tour_url',
        'floor_plan_url',
        'verification_status',
        'passport_id',
        'passport_issued_at',
        'passport_expires_at',
        'lease_certificate_hash',
        'building_permit_hash',
        'ownership_document_hash',
        'status',
        'is_featured',
        'featured_until',
        'featured_tier',
        'slug',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected $casts = [
        'location' => Point::class,
        'features' => 'array',
        'amenities' => 'array',
        'nearby' => 'array',
        'photos' => 'array',
        'videos' => 'array',
        'price' => 'decimal:2',
        'price_per_sqm' => 'decimal:2',
        'nightly_rate' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'rent_to_own_down_payment_pct' => 'decimal:2',
        'rent_to_own_monthly_payment' => 'decimal:2',
        'rent_to_own_total_cost' => 'decimal:2',
        'area_sqm' => 'decimal:2',
        'is_featured' => 'boolean',
        'price_negotiable' => 'boolean',
        'rent_to_own_enabled' => 'boolean',
        'short_term_enabled' => 'boolean',
        'published_at' => 'datetime',
        'sold_at' => 'datetime',
        'featured_until' => 'datetime',
        'passport_issued_at' => 'datetime',
        'passport_expires_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function verifications()
    {
        return $this->hasMany(PropertyVerification::class);
    }

    public function latestVerification()
    {
        return $this->hasOne(PropertyVerification::class)->latestOfMany();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function escrows()
    {
        return $this->hasMany(Escrow::class);
    }

    public function rentToOwnContracts()
    {
        return $this->hasMany(RentToOwnContract::class);
    }

    public function shortTermBookings()
    {
        return $this->hasMany(ShortTermBooking::class);
    }

    public function avmValuations()
    {
        return $this->hasMany(AVMValuation::class);
    }

    public function latestValuation()
    {
        return $this->hasOne(AVMValuation::class)->latestOfMany();
    }

    public function dynamicPricingRecommendations()
    {
        return $this->hasMany(DynamicPricingRecommendation::class);
    }

    public function imageQualityAssessments()
    {
        return $this->hasMany(ImageQualityAssessment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Slug Configuration
    |--------------------------------------------------------------------------
    */

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['title', 'neighborhood'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /*
    |--------------------------------------------------------------------------
    | Media Collections
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(400)
                    ->height(300)
                    ->sharpen(10);

                $this->addMediaConversion('medium')
                    ->width(800)
                    ->height(600);

                $this->addMediaConversion('large')
                    ->width(1600)
                    ->height(1200);
            });

        $this->addMediaCollection('videos')
            ->acceptsMimeTypes(['video/mp4', 'video/webm']);

        $this->addMediaCollection('floor_plan')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);

        $this->addMediaCollection('virtual_tour')
            ->singleFile();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeForSale($query)
    {
        return $query->where('purpose', 'sale');
    }

    public function scopeForRent($query)
    {
        return $query->whereIn('purpose', ['rent', 'short_term']);
    }

    public function scopeRentToOwn($query)
    {
        return $query->where('purpose', 'rent_to_own')
            ->where('rent_to_own_enabled', true);
    }

    public function scopeInNeighborhood($query, string $neighborhood)
    {
        return $query->where('neighborhood', $neighborhood);
    }

    public function scopePriceRange($query, ?float $min, ?float $max)
    {
        return $query->when($min, fn ($q) => $q->where('price', '>=', $min))
            ->when($max, fn ($q) => $q->where('price', '<=', $max));
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 5)
    {
        return $query->whereRaw(
            "ST_Distance_Sphere(location, POINT(?, ?)) <= ?",
            [$lat, $lng, $radiusKm * 1000]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getPriceFormattedAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . $this->price_currency;
    }

    public function getLocationArrayAttribute(): array
    {
        return [
            'lat' => $this->location->getLat(),
            'lng' => $this->location->getLng(),
        ];
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function getPassportUrlAttribute(): ?string
    {
        return $this->passport_id ? route('properties.passport', $this->passport_id) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementInquiryCount(): void
    {
        $this->increment('inquiry_count');
    }

    public function getValuation(): ?AVMValuation
    {
        return $this->latestValuation ?? app(\App\Services\AI\AVMService::class)->valuate($this);
    }

    public function isAvailableForBooking(\Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): bool
    {
        if (!$this->short_term_enabled) {
            return false;
        }

        return !$this->shortTermBookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->exists();
    }
}
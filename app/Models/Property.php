<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'property_type_id',
        'title', 'slug', 'description',
        'operation', 'price', 'currency', 'maintenance_fee',
        'bedrooms', 'bathrooms', 'half_bathrooms', 'parking_spaces',
        'land_area', 'built_area', 'floors', 'age_years',
        'street', 'ext_number', 'int_number', 'postal_code',
        'state_id', 'city_id', 'neighborhood_id',
        'latitude', 'longitude',
        'status', 'published_at', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'maintenance_fee' => 'decimal:2',
            'land_area' => 'decimal:2',
            'built_area' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('order');
    }

    public function cover(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->where('is_cover', true);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Dirección legible, omitiendo las partes que falten. */
    public function getFullAddressAttribute(): string
    {
        return collect([
            trim("{$this->street} {$this->ext_number}"),
            $this->neighborhood?->name,
            $this->city?->name,
            $this->state?->name,
            $this->postal_code ? "C.P. {$this->postal_code}" : null,
        ])->filter()->implode(', ');
    }
}

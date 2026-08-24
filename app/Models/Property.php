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

    /** Condición del inmueble (punto 7 del cliente). */
    public const CONDITIONS = [
        'nueva' => 'Nueva',
        'excelente' => 'Excelente',
        'buena' => 'Buena',
        'regular' => 'Regular',
    ];

    /** Orientación (punto 10). Se usan los 8 puntos cardinales. */
    public const ORIENTATIONS = [
        'norte' => 'Norte',
        'sur' => 'Sur',
        'oriente' => 'Oriente (este)',
        'poniente' => 'Poniente (oeste)',
        'noreste' => 'Noreste',
        'noroeste' => 'Noroeste',
        'sureste' => 'Sureste',
        'suroeste' => 'Suroeste',
    ];

    /** Interior / exterior (punto 11). */
    public const POSITIONS = [
        'interior' => 'Interior',
        'exterior' => 'Exterior',
    ];

    /** Comisión que otorga el propietario en renta (punto 5). */
    public const RENT_COMMISSIONS = [
        'half_month' => 'Medio mes de renta',
        'one_month' => '1 mes de renta',
        'two_three_months' => '2 o 3 meses de renta',
    ];

    /** Campos comerciales: sólo los ve quien tiene properties.view-commission. */
    public const COMMISSION_FIELDS = ['rent_commission', 'sale_commission_percent'];

    protected $fillable = [
        'user_id', 'property_type_id',
        'title', 'slug', 'description',
        'operation', 'price', 'currency', 'maintenance_fee',
        'property_tax_estimate', 'services_estimate',
        'bedrooms', 'bathrooms', 'half_bathrooms', 'parking_spaces',
        'land_area', 'built_area', 'floors', 'floor_number', 'age_years',
        'condition', 'orientation', 'position',
        'street', 'ext_number', 'int_number', 'postal_code',
        'state_id', 'city_id', 'neighborhood_id',
        'latitude', 'longitude',
        'status', 'published_at', 'is_featured',
        'is_exclusive', 'rent_commission', 'sale_commission_percent',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'maintenance_fee' => 'decimal:2',
            'property_tax_estimate' => 'decimal:2',
            'services_estimate' => 'decimal:2',
            'sale_commission_percent' => 'decimal:2',
            'land_area' => 'decimal:2',
            'built_area' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_exclusive' => 'boolean',
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

    /** Etiquetas legibles de los campos de catálogo. */
    public function getConditionLabelAttribute(): ?string
    {
        return self::CONDITIONS[$this->condition] ?? null;
    }

    public function getOrientationLabelAttribute(): ?string
    {
        return self::ORIENTATIONS[$this->orientation] ?? null;
    }

    public function getPositionLabelAttribute(): ?string
    {
        return self::POSITIONS[$this->position] ?? null;
    }

    public function getRentCommissionLabelAttribute(): ?string
    {
        return self::RENT_COMMISSIONS[$this->rent_commission] ?? null;
    }

    /**
     * Costo mensual estimado aparte del precio: mantenimiento + servicios +
     * el predial prorrateado (en México se paga anual). Null si no se capturó
     * ninguno de los tres.
     */
    public function getEstimatedMonthlyCostAttribute(): ?float
    {
        $parts = [
            (float) $this->maintenance_fee,
            (float) $this->services_estimate,
            (float) $this->property_tax_estimate / 12,
        ];

        return array_sum($parts) > 0 ? round(array_sum($parts), 2) : null;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    use HasFactory;

    /** Grupos del catálogo, en el orden en que se muestran. */
    public const GROUPS = [
        'amenidad' => 'Amenidades',
        'recreacion' => 'Recreación',
        'caracteristica' => 'Características del inmueble',
    ];

    protected $fillable = ['name', 'slug', 'group', 'icon', 'order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Orden estable del catálogo: por grupo, luego por posición y nombre.
     * Se usa CASE en vez de FIELD() para que también funcione en SQLite
     * (la base de las pruebas).
     */
    public function scopeOrdered($query)
    {
        $cases = [];
        $bindings = [];

        foreach (array_keys(self::GROUPS) as $position => $group) {
            $cases[] = 'WHEN ? THEN ?';
            $bindings[] = $group;
            $bindings[] = $position;
        }

        return $query
            ->orderByRaw('CASE `group` '.implode(' ', $cases).' ELSE 99 END', $bindings)
            ->orderBy('order')
            ->orderBy('name');
    }

    public function getGroupLabelAttribute(): string
    {
        return self::GROUPS[$this->group] ?? $this->group;
    }
}

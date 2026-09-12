<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\State;
use App\Services\PropertyPdfService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class PublicPropertyController extends Controller
{
    /** Criterios de orden ofrecidos en el catálogo. */
    public const SORTS = [
        'recent' => 'Más recientes',
        'price_asc' => 'Precio: de menor a mayor',
        'price_desc' => 'Precio: de mayor a menor',
        'area_desc' => 'Superficie: de mayor a menor',
    ];

    /** Filtros que cuentan como "búsqueda activa" (sin ellos se muestra la portada). */
    private const FILTER_KEYS = [
        'q', 'type', 'operation', 'state', 'min_price', 'max_price', 'bedrooms', 'features',
    ];

    public function index(Request $request)
    {
        $properties = Property::query()
            ->published()
            ->with(['type', 'city', 'state', 'cover'])
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $term = $request->string('q')->value();
                $q->where(fn ($sub) => $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%"));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('property_type_id', $request->type))
            ->when($request->filled('operation'), fn ($q) => $q->where('operation', $request->operation))
            ->when($request->filled('state'), fn ($q) => $q->where('state_id', $request->state))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->filled('bedrooms'), fn ($q) => $q->where('bedrooms', '>=', $request->bedrooms))
            // Amenidades: se piden TODAS las marcadas, no cualquiera de ellas.
            ->when($request->filled('features'), function (Builder $q) use ($request) {
                foreach (array_filter(array_map('intval', (array) $request->input('features'))) as $featureId) {
                    $q->whereHas('features', fn ($sub) => $sub->where('features.id', $featureId));
                }
            })
            ->tap(fn (Builder $q) => $this->applyBounds($q, $request))
            ->tap(fn (Builder $q) => $this->applySort($q, $request))
            ->paginate(12)
            ->withQueryString();

        $features = Feature::active()->ordered()->get();

        return view('public.index', [
            'properties' => $properties,
            'types' => PropertyType::active()->orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
            'features' => $features->groupBy('group'),
            'mapPoints' => $this->mapPoints($properties),
            'activeFilters' => $this->activeFilters($request, $features),
            'isSearching' => $request->hasAny(self::FILTER_KEYS) || $request->filled('bounds'),
            'sort' => $this->sortKey($request),
        ]);
    }

    public function show(Property $property)
    {
        abort_unless($property->isPublished(), 404);

        $property->load([
            'type', 'state', 'city', 'neighborhood', 'images', 'user',
            'features' => fn ($q) => $q->ordered(),
        ]);
        $property->increment('views_count');

        $similar = Property::published()
            ->where('id', '!=', $property->id)
            ->where('property_type_id', $property->property_type_id)
            ->with(['type', 'city', 'cover'])
            ->take(3)
            ->get();

        return view('public.show', compact('property', 'similar'));
    }

    /** Ficha técnica descargable (punto 14 del cliente). */
    public function pdf(Property $property, PropertyPdfService $pdf): Response
    {
        abort_unless($property->isPublished(), 404);

        return $pdf->build($property)->download($pdf->filename($property));
    }

    /**
     * "Buscar en esta zona": el mapa manda su recuadro visible como
     * sur,oeste,norte,este y se filtra por coordenadas dentro de él.
     */
    private function applyBounds(Builder $query, Request $request): void
    {
        $bounds = array_map('floatval', array_filter(explode(',', (string) $request->input('bounds'), 5), 'is_numeric'));

        if (count($bounds) !== 4) {
            return;
        }

        [$south, $west, $north, $east] = $bounds;

        if ($south >= $north || $west >= $east) {
            return;
        }

        $query->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east]);
    }

    private function applySort(Builder $query, Request $request): void
    {
        match ($this->sortKey($request)) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'area_desc' => $query->orderByRaw('COALESCE(built_area, land_area, 0) desc'),
            default => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
        };
    }

    private function sortKey(Request $request): string
    {
        $sort = (string) $request->input('sort');

        return array_key_exists($sort, self::SORTS) ? $sort : 'recent';
    }

    /**
     * Pines del mapa: sólo los resultados de la página actual que tienen
     * coordenadas, para que la lista y el mapa muestren lo mismo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapPoints(LengthAwarePaginator $properties): array
    {
        return $properties->getCollection()
            ->filter(fn (Property $p) => $p->hasCoordinates())
            ->map(fn (Property $p) => [
                'id' => $p->id,
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'price' => $this->shortPrice($p),
                'title' => $p->title,
                'meta' => collect([
                    $p->bedrooms ? "{$p->bedrooms} rec" : null,
                    $p->bathrooms ? "{$p->bathrooms} baños" : null,
                    $p->built_area ? (int) $p->built_area.' m²' : null,
                ])->filter()->implode(' · '),
                'url' => route('public.properties.show', $p->slug),
                'image' => $p->cover?->thumb_url,
            ])
            ->values()
            ->all();
    }

    /** $5,200,000 se vuelve $5.2M para que quepa en un pin. */
    private function shortPrice(Property $property): string
    {
        $price = (float) $property->price;

        return match (true) {
            $price >= 1_000_000 => '$'.rtrim(rtrim(number_format($price / 1_000_000, 1), '0'), '.').'M',
            $price >= 1_000 => '$'.round($price / 1_000).'k',
            default => '$'.number_format($price),
        };
    }

    /**
     * Etiquetas de los filtros aplicados, cada una con la consulta que queda
     * al quitarla, para poder cerrarlas una por una.
     *
     * @return array<int, array{label: string, query: array<string, mixed>}>
     */
    private function activeFilters(Request $request, Collection $features): array
    {
        $query = $request->query();
        $labels = [];

        $simple = [
            'q' => fn ($v) => "“{$v}”",
            'type' => fn ($v) => PropertyType::find($v)?->name,
            'operation' => fn ($v) => ['sale' => 'En venta', 'rent' => 'En renta'][$v] ?? null,
            'state' => fn ($v) => State::find($v)?->name,
            'min_price' => fn ($v) => 'Desde $'.number_format((float) $v),
            'max_price' => fn ($v) => 'Hasta $'.number_format((float) $v),
            'bedrooms' => fn ($v) => "{$v}+ recámaras",
        ];

        foreach ($simple as $key => $label) {
            if ($request->filled($key) && ($text = $label($request->input($key)))) {
                $labels[] = ['label' => $text, 'query' => collect($query)->except([$key, 'page'])->all()];
            }
        }

        foreach (array_filter(array_map('intval', (array) $request->input('features', []))) as $id) {
            if (! $feature = $features->firstWhere('id', $id)) {
                continue;
            }

            $rest = array_values(array_diff(array_map('intval', (array) $request->input('features')), [$id]));
            $labels[] = [
                'label' => $feature->name,
                'query' => collect($query)->except('page')->merge(['features' => $rest ?: null])->filter()->all(),
            ];
        }

        if ($request->filled('bounds')) {
            $labels[] = ['label' => 'En la zona del mapa', 'query' => collect($query)->except(['bounds', 'page'])->all()];
        }

        return $labels;
    }
}

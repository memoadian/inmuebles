{{-- Ficha técnica para dompdf: sin flex ni grid, todo con tablas. --}}
@php
    $operationLabel = ['sale' => 'En venta', 'rent' => 'En renta', 'both' => 'En venta o renta'][$property->operation];
    $grouped = $property->features->groupBy('group');

    $specs = collect([
        'Tipo de inmueble' => $property->type?->name,
        'Recámaras' => $property->bedrooms ?: null,
        'Baños' => $property->bathrooms ?: null,
        'Medios baños' => $property->half_bathrooms ?: null,
        'Estacionamientos' => $property->parking_spaces ?: null,
        'Construcción' => $property->built_area ? (int) $property->built_area.' m²' : null,
        'Terreno' => $property->land_area ? (int) $property->land_area.' m²' : null,
        'Niveles de la propiedad' => $property->floors,
        'Piso en el que se encuentra' => $property->floor_number,
        'Condición' => $property->condition_label,
        'Orientación' => $property->orientation_label,
        'Ubicación en el edificio' => $property->position_label,
        'Antigüedad' => $property->age_years !== null
            ? $property->age_years.' '.\Illuminate\Support\Str::plural('año', $property->age_years)
            : null,
        'Publicado el' => $property->published_at?->translatedFormat('d/m/Y'),
    ])->filter(fn ($value) => $value !== null && $value !== '');
@endphp

<style>
    @page { margin: 28px 32px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1c1917; }
    h1 { font-size: 17px; margin: 0 0 3px; color: #0b1440; }
    h2 { font-size: 11px; margin: 0 0 6px; color: #1b3ac4; text-transform: uppercase; letter-spacing: .5px; }
    p { margin: 0 0 6px; line-height: 1.5; }
    .muted { color: #78716c; }
    .brand { color: #1b3ac4; }
    .header { border-bottom: 2px solid #1b3ac4; padding-bottom: 8px; margin-bottom: 12px; }
    .price { font-size: 20px; font-weight: bold; color: #1b3ac4; }
    .block { margin-bottom: 14px; }
    .cover { width: 100%; height: auto; border: 1px solid #e7e5e4; }
    table { width: 100%; border-collapse: collapse; }
    .specs td { padding: 4px 6px; border-bottom: 1px solid #f0efed; vertical-align: top; }
    .specs td.term { color: #78716c; width: 46%; }
    .specs td.value { font-weight: bold; text-align: right; }
    .gallery td { padding: 2px; width: 25%; }
    .gallery img { width: 100%; height: auto; border: 1px solid #e7e5e4; }
    .contact { background: #f5f5f4; border: 1px solid #e7e5e4; padding: 10px; }
    .footer { margin-top: 14px; border-top: 1px solid #e7e5e4; padding-top: 6px; font-size: 8px; color: #a8a29e; }
</style>

<div class="header">
    <table>
        <tr>
            <td>
                <h1>{{ $property->title }}</h1>
                <p class="muted">{{ $property->full_address }}</p>
            </td>
            <td style="text-align: right; vertical-align: top; width: 38%;">
                <p class="brand" style="margin-bottom: 2px;">{{ $operationLabel }}</p>
                <p class="price">${{ number_format($property->price, 0) }} {{ $property->currency }}</p>
            </td>
        </tr>
    </table>
</div>

@if ($images)
    <div class="block">
        <img src="{{ $images[0] }}" class="cover" alt="">
    </div>
@endif

<table class="block">
    <tr>
        <td style="width: 52%; vertical-align: top; padding-right: 12px;">
            <h2>Ficha técnica</h2>
            <table class="specs">
                @foreach ($specs as $term => $value)
                    <tr>
                        <td class="term">{{ $term }}</td>
                        <td class="value">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
        <td style="vertical-align: top;">
            @if ($property->estimated_monthly_cost)
                <h2>Costos estimados</h2>
                <table class="specs" style="margin-bottom: 12px;">
                    @if ($property->maintenance_fee)
                        <tr>
                            <td class="term">Mantenimiento</td>
                            <td class="value">${{ number_format($property->maintenance_fee, 0) }} / mes</td>
                        </tr>
                    @endif
                    @if ($property->services_estimate)
                        <tr>
                            <td class="term">Servicios</td>
                            <td class="value">${{ number_format($property->services_estimate, 0) }} / mes</td>
                        </tr>
                    @endif
                    @if ($property->property_tax_estimate)
                        <tr>
                            <td class="term">Predial</td>
                            <td class="value">${{ number_format($property->property_tax_estimate, 0) }} / año</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="term">Total mensual aprox.</td>
                        <td class="value brand">${{ number_format($property->estimated_monthly_cost, 0) }}</td>
                    </tr>
                </table>
            @endif

            <div class="contact">
                <h2>Contacto</h2>
                <p style="font-weight: bold; margin-bottom: 2px;">{{ $property->user->name }}</p>
                @if ($property->user->phone)
                    <p class="muted" style="margin-bottom: 2px;">Tel. {{ $property->user->phone }}</p>
                @endif
                <p class="muted" style="margin-bottom: 0;">{{ $property->user->email }}</p>
            </div>
        </td>
    </tr>
</table>

@if ($property->description)
    <div class="block">
        <h2>Descripción</h2>
        <p>{{ $property->description }}</p>
    </div>
@endif

@foreach (\App\Models\Feature::GROUPS as $group => $groupLabel)
    @if ($grouped->has($group))
        <div class="block">
            <h2>{{ $groupLabel }}</h2>
            <p>{{ $grouped[$group]->pluck('name')->implode(' · ') }}</p>
        </div>
    @endif
@endforeach

@if (count($images) > 1)
    <div class="block">
        <h2>Más fotos</h2>
        <table class="gallery">
            <tr>
                @foreach (array_slice($images, 1) as $image)
                    <td><img src="{{ $image }}" alt=""></td>
                @endforeach
            </tr>
        </table>
    </div>
@endif

<div class="footer">
    {{ config('app.name') }} · Ficha generada el {{ $generatedAt->translatedFormat('d/m/Y') }} ·
    {{ route('public.properties.show', $property->slug) }}
</div>

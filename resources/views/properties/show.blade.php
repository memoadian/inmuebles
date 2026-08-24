@extends('layouts.app')

@section('title', $property->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <x-property-status :status="$property->status" />
            <span class="text-sm text-slate-500">{{ $property->type?->name }}</span>
            @if ($property->is_exclusive)
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                    <i class="bi bi-star-fill"></i> En exclusiva
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if ($property->isPublished())
                <a href="{{ route('public.properties.pdf', $property->slug) }}"
                   class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">
                    <i class="bi bi-file-earmark-arrow-down"></i> Ficha PDF
                </a>
            @endif

            @can('update', $property)
                <a href="{{ route('properties.edit', $property) }}"
                   class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    <i class="bi bi-pencil"></i> Editar
                </a>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            @if ($property->images->isNotEmpty())
                <div class="grid gap-2 grid-cols-2 sm:grid-cols-3">
                    @foreach ($property->images as $image)
                        <a href="{{ $image->url }}" target="_blank" class="block rounded-lg overflow-hidden border border-slate-200">
                            <img src="{{ $image->thumb_url }}" alt="" class="aspect-[4/3] w-full object-cover">
                        </a>
                    @endforeach
                </div>
            @endif

            <section class="bg-white rounded-xl border border-slate-200 p-4">
                <h1 class="text-xl font-semibold text-slate-900">{{ $property->title }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $property->full_address }}</p>

                @if ($property->description)
                    <p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $property->description }}</p>
                @endif
            </section>

            @if ($property->features->isNotEmpty())
                @php $grouped = $property->features->groupBy('group'); @endphp
                <section class="bg-white rounded-xl border border-slate-200 p-4 space-y-4">
                    @foreach (\App\Models\Feature::GROUPS as $group => $groupLabel)
                        @if ($grouped->has($group))
                            <div>
                                <h2 class="font-medium text-slate-800 mb-3">{{ $groupLabel }}</h2>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($grouped[$group] as $feature)
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
                                            {{ $feature->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </section>
            @endif
        </div>

        <div class="space-y-6">
            <section class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-semibold text-slate-900">
                    ${{ number_format($property->price, 2) }}
                    <span class="text-base font-normal text-slate-500">{{ $property->currency }}</span>
                </p>
                @if ($property->maintenance_fee)
                    <p class="mt-1 text-sm text-slate-500">
                        Mantenimiento: ${{ number_format($property->maintenance_fee, 2) }}
                    </p>
                @endif
                @if ($property->services_estimate)
                    <p class="text-sm text-slate-500">
                        Servicios: ${{ number_format($property->services_estimate, 2) }} al mes
                    </p>
                @endif
                @if ($property->property_tax_estimate)
                    <p class="text-sm text-slate-500">
                        Predial: ${{ number_format($property->property_tax_estimate, 2) }} al año
                    </p>
                @endif
            </section>

            @can('properties.view-commission')
                @if ($property->rent_commission || $property->sale_commission_percent)
                    <section class="bg-white rounded-xl border border-slate-200 p-4">
                        <h2 class="font-medium text-slate-800 mb-1">Comisiones</h2>
                        <p class="text-xs text-slate-500 mb-3">Uso interno; no se muestra en el catálogo.</p>
                        <dl class="space-y-2 text-sm">
                            @if ($property->rent_commission)
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Renta</dt>
                                    <dd class="font-medium text-slate-800">{{ $property->rent_commission_label }}</dd>
                                </div>
                            @endif
                            @if ($property->sale_commission_percent)
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Venta</dt>
                                    <dd class="font-medium text-slate-800">{{ rtrim(rtrim($property->sale_commission_percent, '0'), '.') }} %</dd>
                                </div>
                            @endif
                        </dl>
                    </section>
                @endif
            @endcan

            <section class="bg-white rounded-xl border border-slate-200 p-4">
                <h2 class="font-medium text-slate-800 mb-3">Ficha técnica</h2>
                <dl class="space-y-2 text-sm">
                    @foreach ([
                        'Recámaras' => $property->bedrooms,
                        'Baños' => $property->bathrooms,
                        'Medios baños' => $property->half_bathrooms,
                        'Estacionamientos' => $property->parking_spaces,
                        'Terreno' => $property->land_area ? (int) $property->land_area.' m²' : null,
                        'Construcción' => $property->built_area ? (int) $property->built_area.' m²' : null,
                        'Niveles' => $property->floors,
                        'Piso' => $property->floor_number,
                        'Condición' => $property->condition_label,
                        'Orientación' => $property->orientation_label,
                        'Interior/Exterior' => $property->position_label,
                        'Antigüedad' => $property->age_years ? $property->age_years.' años' : null,
                        'Creada el' => $property->created_at?->translatedFormat('d/m/Y'),
                    ] as $term => $value)
                        @if ($value !== null && $value !== '')
                            <div class="flex justify-between gap-2">
                                <dt class="text-slate-500">{{ $term }}</dt>
                                <dd class="font-medium text-slate-800">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>

            <section class="bg-white rounded-xl border border-slate-200 p-4">
                <h2 class="font-medium text-slate-800 mb-2">Publicado por</h2>
                <p class="text-sm text-slate-800">{{ $property->user->name }}</p>
                <p class="text-sm text-slate-500">{{ $property->user->email }}</p>
                @if ($property->user->phone)
                    <p class="text-sm text-slate-500">{{ $property->user->phone }}</p>
                @endif
            </section>
        </div>
    </div>
@endsection

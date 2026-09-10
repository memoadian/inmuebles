@extends('layouts.public')

@php
    $operationLabel = ['sale' => 'en venta', 'rent' => 'en renta', 'both' => 'en venta o renta'][$property->operation];
    $locationLabel = collect([$property->city?->name, $property->state?->name])->filter()->implode(', ');
    $seoTitle = trim("{$property->title} {$operationLabel}".($locationLabel ? " en {$locationLabel}" : ''));

    $metaDescription = collect([
        "{$property->title} {$operationLabel}".($locationLabel ? " en {$locationLabel}" : '').'.',
        $property->bedrooms ? "{$property->bedrooms} recámaras" : null,
        $property->bathrooms ? "{$property->bathrooms} baños" : null,
        $property->built_area ? (int) $property->built_area.' m² construidos' : null,
    ])->filter()->implode(' ');
    $metaDescription = \Illuminate\Support\Str::limit($metaDescription.' $'.number_format($property->price, 0).' '.$property->currency.'.', 160);
@endphp

@section('title', $seoTitle)
@section('meta_description', $metaDescription)
@section('og_type', 'website')
@if ($property->cover)
    @section('og_image', $property->cover->url)
@endif

@section('content')
    <x-property-structured-data :property="$property" />
    <div class="mx-auto max-w-7xl px-4 py-6">
        <a href="{{ route('public.properties.index') }}"
           class="inline-flex items-center gap-1.5 mb-5 text-sm text-slate-600 hover:text-brand-700">
            <i class="bi bi-arrow-left"></i> Volver al catálogo
        </a>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                @if ($property->images->isNotEmpty())
                    @php $imageCount = $property->images->count(); @endphp
                    <div data-gallery
                         data-alt="{{ $property->title }}"
                         data-images='@json($property->images->pluck('url'))'
                         class="rounded-2xl overflow-hidden border border-slate-200 bg-white">
                        <div class="relative">
                            <button type="button" data-gallery-open="0"
                                    class="group block w-full cursor-zoom-in"
                                    aria-label="Abrir galería de fotos">
                                <img src="{{ $property->images->first()->url }}" alt="{{ $property->title }}"
                                     class="w-full aspect-[16/10] object-cover transition-opacity group-hover:opacity-95">
                            </button>
                            <span class="absolute top-4 left-4 rounded-full bg-white/95 backdrop-blur px-3 py-1 text-xs font-medium text-brand-800 shadow-sm">
                                {{ $property->operation === 'rent' ? 'En renta' : 'En venta' }}
                            </span>
                            @if ($imageCount > 1)
                                <span class="pointer-events-none absolute bottom-4 right-4 inline-flex items-center gap-1.5 rounded-full bg-slate-950/60 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
                                    <i class="bi bi-images"></i> {{ $imageCount }} fotos
                                </span>
                            @endif
                        </div>

                        @if ($imageCount > 1)
                            <div class="grid grid-cols-4 gap-1 p-1">
                                @foreach ($property->images->slice(1, 4) as $image)
                                    <button type="button" data-gallery-open="{{ $loop->iteration }}"
                                            class="group relative block overflow-hidden rounded-lg cursor-zoom-in"
                                            aria-label="Abrir galería de fotos">
                                        <img src="{{ $image->thumb_url }}" alt=""
                                             class="aspect-[4/3] w-full object-cover transition-opacity group-hover:opacity-90">
                                        @if ($loop->last && $imageCount > 5)
                                            <span class="absolute inset-0 flex items-center justify-center bg-slate-950/55 text-lg font-semibold text-white">
                                                +{{ $imageCount - 5 }}
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <section class="bg-white rounded-2xl border border-slate-200 p-5 md:p-6">
                    <h1 class="font-serif text-2xl md:text-3xl font-semibold text-brand-950">{{ $property->title }}</h1>
                    <p class="mt-1.5 flex items-center gap-1.5 text-slate-500">
                        <i class="bi bi-geo-alt"></i> {{ $property->full_address }}
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-sm text-slate-700">
                            <i class="bi bi-door-closed text-brand-600"></i> {{ $property->bedrooms }} recámaras
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-sm text-slate-700">
                            <i class="bi bi-droplet text-brand-600"></i> {{ $property->bathrooms }} baños
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-sm text-slate-700">
                            <i class="bi bi-car-front text-brand-600"></i> {{ $property->parking_spaces }} estacionamientos
                        </span>
                        @if ($property->built_area)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-sm text-slate-700">
                                <i class="bi bi-rulers text-brand-600"></i> {{ (int) $property->built_area }} m² construidos
                            </span>
                        @endif
                        @if ($property->land_area)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-sm text-slate-700">
                                <i class="bi bi-map text-brand-600"></i> {{ (int) $property->land_area }} m² de terreno
                            </span>
                        @endif
                    </div>

                    @if ($property->description)
                        <p class="mt-5 whitespace-pre-line leading-relaxed text-slate-700">{{ $property->description }}</p>
                    @endif
                </section>

                @php
                    // Ficha técnica: sólo se listan los datos que sí se capturaron.
                    $specs = collect([
                        'Tipo de inmueble' => $property->type?->name,
                        'Condición' => $property->condition_label,
                        'Niveles de la propiedad' => $property->floors,
                        'Piso en el que se encuentra' => $property->floor_number,
                        'Orientación' => $property->orientation_label,
                        'Ubicación en el edificio' => $property->position_label,
                        'Antigüedad' => $property->age_years !== null
                            ? $property->age_years.' '.\Illuminate\Support\Str::plural('año', $property->age_years)
                            : null,
                        'Medios baños' => $property->half_bathrooms ?: null,
                        'Publicado el' => $property->published_at?->translatedFormat('d \d\e F \d\e Y'),
                        'Última actualización' => $property->updated_at?->diffForHumans(),
                    ])->filter(fn ($value) => $value !== null && $value !== '');
                @endphp

                @if ($specs->isNotEmpty())
                    <section class="bg-white rounded-2xl border border-slate-200 p-5 md:p-6">
                        <h2 class="font-serif text-lg font-semibold text-brand-950 mb-4">Ficha técnica</h2>
                        <dl class="grid gap-x-8 gap-y-3 sm:grid-cols-2">
                            @foreach ($specs as $term => $value)
                                <div class="flex items-baseline justify-between gap-4 border-b border-slate-100 pb-2">
                                    <dt class="text-sm text-slate-500">{{ $term }}</dt>
                                    <dd class="text-sm font-medium text-slate-800 text-right">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                @if ($property->features->isNotEmpty())
                    @php $grouped = $property->features->groupBy('group'); @endphp
                    <section class="bg-white rounded-2xl border border-slate-200 p-5 md:p-6 space-y-5">
                        @foreach (\App\Models\Feature::GROUPS as $group => $groupLabel)
                            @if ($grouped->has($group))
                                <div>
                                    <h2 class="font-serif text-lg font-semibold text-brand-950 mb-4">{{ $groupLabel }}</h2>
                                    <div class="grid gap-3 sm:grid-cols-3 text-sm text-slate-700">
                                        @foreach ($grouped[$group] as $feature)
                                            <span class="inline-flex items-center gap-2">
                                                <i class="bi bi-check-circle-fill text-brand-600"></i> {{ $feature->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </section>
                @endif

                @if ($property->hasCoordinates())
                    <section class="bg-white rounded-2xl border border-slate-200 p-5 md:p-6">
                        <h2 class="font-serif text-lg font-semibold text-brand-950 mb-1">Ubicación</h2>
                        <p class="text-sm text-slate-500 mb-4">
                            Zona aproximada del inmueble. La dirección exacta se comparte al contactar al asesor.
                        </p>
                        <div data-map-wrapper class="relative">
                            <div id="propertyMap"
                                 data-lat="{{ $property->latitude }}"
                                 data-lng="{{ $property->longitude }}"
                                 data-height="h-96 md:h-[30rem]"
                                 class="h-96 md:h-[30rem] w-full rounded-xl border border-slate-200 bg-slate-100 z-0"></div>

                            <button type="button" data-map-expand
                                    aria-label="Ampliar el mapa a pantalla completa"
                                    class="absolute top-3 right-3 z-[500] inline-flex items-center gap-1.5 rounded-xl border
                                           border-slate-300 bg-white/95 px-3 py-1.5 text-xs font-medium text-slate-700
                                           shadow-sm backdrop-blur hover:bg-white">
                                <i class="bi bi-arrows-fullscreen" data-map-expand-icon></i>
                                <span class="hidden sm:inline" data-map-expand-label>Ampliar</span>
                            </button>
                        </div>
                    </section>
                @endif
            </div>

            <div class="space-y-6">
                <section class="bg-white rounded-2xl border border-slate-200 p-5 md:p-6 lg:sticky lg:top-24">
                    <p class="text-sm font-medium text-brand-700">
                        {{ ['sale' => 'En venta', 'rent' => 'En renta', 'both' => 'Venta o renta'][$property->operation] }}
                    </p>
                    <p class="mt-1 font-serif text-3xl font-semibold text-brand-950">
                        ${{ number_format($property->price, 0) }}
                        <span class="font-sans text-lg font-normal text-slate-500">{{ $property->currency }}</span>
                    </p>
                    @if ($property->estimated_monthly_cost)
                        <div class="mt-3 rounded-xl bg-slate-50 border border-slate-200 p-3">
                            <p class="text-sm font-medium text-slate-700">
                                + ${{ number_format($property->estimated_monthly_cost, 0) }} al mes estimados
                            </p>
                            <ul class="mt-1.5 space-y-0.5 text-xs text-slate-500">
                                @if ($property->maintenance_fee)
                                    <li>Mantenimiento: ${{ number_format($property->maintenance_fee, 0) }}</li>
                                @endif
                                @if ($property->services_estimate)
                                    <li>Servicios: ${{ number_format($property->services_estimate, 0) }}</li>
                                @endif
                                @if ($property->property_tax_estimate)
                                    <li>Predial: ${{ number_format($property->property_tax_estimate, 0) }} al año</li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <div class="mt-5 pt-5 border-t border-slate-100">
                        <p class="text-sm text-slate-500">Publicado por</p>
                        <p class="font-medium text-slate-800">{{ $property->user->name }}</p>

                        @if ($property->user->phone)
                            <a href="tel:{{ $property->user->phone }}"
                               class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5
                                      text-sm font-medium text-white shadow-sm hover:bg-brand-800 transition-colors">
                                <i class="bi bi-telephone"></i> {{ $property->user->phone }}
                            </a>
                        @endif

                        <a href="mailto:{{ $property->user->email }}?subject={{ rawurlencode('Interés en: '.$property->title) }}"
                           class="mt-2 flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5
                                  text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="bi bi-envelope"></i> Enviar correo
                        </a>
                    </div>

                    <div class="mt-5 pt-5 border-t border-slate-100">
                        <a href="{{ route('public.properties.pdf', $property->slug) }}"
                           class="flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5
                                  text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="bi bi-file-earmark-arrow-down"></i> Descargar ficha en PDF
                        </a>

                        <p class="mt-4 mb-2 text-sm text-slate-500">Compartir</p>
                        <x-share-buttons :url="url()->current()" :title="$property->title" />
                    </div>
                </section>
            </div>
        </div>

        @if ($similar->isNotEmpty())
            <section class="mt-10">
                <h2 class="font-serif text-xl font-semibold text-brand-950 mb-4">Inmuebles similares</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($similar as $item)
                        <a href="{{ route('public.properties.show', $item->slug) }}"
                           class="group bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                            <div class="aspect-[4/3] bg-slate-100">
                                @if ($item->cover)
                                    <img src="{{ $item->cover->thumb_url }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <div class="p-3.5">
                                <p class="font-serif font-semibold text-brand-900">${{ number_format($item->price, 0) }}</p>
                                <p class="text-sm text-slate-600 line-clamp-1 group-hover:text-brand-700 transition-colors">{{ $item->title }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/map.js', 'resources/js/share.js', 'resources/js/gallery.js'])
@endpush

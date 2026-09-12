@extends('layouts.public')

@php
    $selectedType = $types->firstWhere('id', (int) request('type'));
    $selectedState = $states->firstWhere('id', (int) request('state'));
    $operationLabel = ['sale' => 'en venta', 'rent' => 'en renta'][request('operation')] ?? 'en venta y renta';

    $seoTitle = trim(collect([$selectedType?->name ?? 'Inmuebles', $operationLabel, $selectedState ? "en {$selectedState->name}" : null])->filter()->implode(' '));
    $total = $properties->total();
    $metaDescription = "{$seoTitle}. Explora {$total} "
        .\Illuminate\Support\Str::plural('propiedad', $total).' '
        .($total === 1 ? 'publicada' : 'publicadas')
        .' directamente por sus dueños y agentes en México.';

    // La portada sólo vive en la home sin búsqueda: en el catálogo mandan los resultados.
    $showHero = request()->routeIs('home') && ! $isSearching;
    $showMap = request('view') !== 'grid';
    $selectedFeatures = array_map('intval', (array) request('features', []));
    $field = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-[13px] text-slate-700 outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15';
@endphp

@section('title', $seoTitle)
@section('meta_description', $metaDescription)

@section('content')

@if ($showHero)
    {{-- ───────────────── Portada ───────────────── --}}
    <section class="bg-brand-950 px-5 pb-14 pt-16 text-slate-100">
        <div class="mx-auto max-w-[1600px]">
            <h1 class="max-w-3xl font-display text-5xl leading-[0.98] tracking-tight text-white sm:text-6xl lg:text-7xl">
                Cada casa que ves<br>sigue disponible<span class="text-accent-400">.</span>
            </h1>
            <p class="mt-5 max-w-md text-[15px] leading-relaxed text-slate-400">
                Inventario publicado directamente por dueños y agentes, con la ubicación exacta en el mapa
                y la ficha completa de cada inmueble.
            </p>

            <form method="GET" action="{{ route('public.properties.index') }}"
                  class="mt-8 flex max-w-3xl flex-col gap-2 rounded-3xl bg-slate-50 p-2 sm:flex-row sm:items-center sm:rounded-full sm:pl-2.5">
                <div class="flex shrink-0 rounded-full bg-slate-200/70 p-1">
                    @foreach (['' => 'Todo', 'sale' => 'Venta', 'rent' => 'Renta'] as $value => $text)
                        <label class="cursor-pointer">
                            <input type="radio" name="operation" value="{{ $value }}" class="peer sr-only"
                                   @checked((string) request('operation', '') === $value)>
                            <span class="block rounded-full px-4 py-1.5 text-[13px] font-semibold text-slate-500 transition-colors
                                         peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm">
                                {{ $text }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Colonia, ciudad o tipo de inmueble…"
                       class="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-[14px] text-slate-800 outline-none placeholder:text-slate-400">

                <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-accent-500 px-7 py-3 text-[14px] font-semibold text-white transition-colors hover:bg-accent-600">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </form>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                @foreach ([
                    ['label' => '3+ recámaras', 'query' => ['bedrooms' => 3]],
                    ['label' => 'Hasta $6M', 'query' => ['max_price' => 6000000, 'operation' => 'sale']],
                    ['label' => 'Renta hasta $20k', 'query' => ['max_price' => 20000, 'operation' => 'rent']],
                ] as $chip)
                    <a href="{{ route('public.properties.index', $chip['query']) }}"
                       class="rounded-full border border-white/20 px-3.5 py-1.5 text-[12.5px] text-slate-300 transition-colors hover:border-white/50 hover:text-white">
                        {{ $chip['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-[1600px] px-5 py-12">
        <div class="mb-6 flex items-baseline justify-between gap-4">
            <h2 class="font-display text-3xl tracking-tight text-slate-900">Recién publicados</h2>
            <a href="{{ route('public.properties.index') }}"
               class="shrink-0 text-[13.5px] font-medium text-brand-700 hover:text-brand-900">
                Ver los {{ $total }} inmuebles <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @if ($properties->isEmpty())
            <p class="rounded-2xl border border-slate-200 bg-white px-4 py-16 text-center text-sm text-slate-500">
                Todavía no hay inmuebles publicados.
            </p>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($properties->take(4) as $property)
                    @include('public._card', ['property' => $property])
                @endforeach
            </div>
        @endif
    </section>

@else
    {{-- ───────────────── Resultados ───────────────── --}}
    <form method="GET" action="{{ route('public.properties.index') }}" data-catalog-form
          class="sticky top-18 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <input type="hidden" name="bounds" value="{{ request('bounds') }}" data-bounds-input>
        @unless ($showMap)
            <input type="hidden" name="view" value="grid">
        @endunless

        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-2 px-5 py-2.5">
            <div class="flex min-w-56 flex-1 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 focus-within:border-brand-600 focus-within:ring-2 focus-within:ring-brand-600/15">
                <i class="bi bi-search text-[13px] text-slate-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Colonia, ciudad o inmueble"
                       class="min-w-0 flex-1 bg-transparent text-[13px] text-slate-700 outline-none placeholder:text-slate-400">
            </div>

            <select name="operation" class="{{ $field }}" data-autosubmit>
                <option value="">Venta y renta</option>
                <option value="sale" @selected(request('operation') === 'sale')>Venta</option>
                <option value="rent" @selected(request('operation') === 'rent')>Renta</option>
            </select>

            <select name="type" class="{{ $field }}" data-autosubmit>
                <option value="">Tipo</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>

            <select name="state" class="{{ $field }}" data-autosubmit>
                <option value="">Estado</option>
                @foreach ($states as $state)
                    <option value="{{ $state->id }}" @selected(request('state') == $state->id)>{{ $state->name }}</option>
                @endforeach
            </select>

            <select name="bedrooms" class="{{ $field }}" data-autosubmit>
                <option value="">Recámaras</option>
                @foreach ([1, 2, 3, 4] as $n)
                    <option value="{{ $n }}" @selected(request('bedrooms') == $n)>{{ $n }}+</option>
                @endforeach
            </select>

            <div class="flex items-center gap-1">
                <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Precio mín."
                       class="{{ $field }} w-28">
                <span class="text-slate-400">–</span>
                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="máx."
                       class="{{ $field }} w-24">
            </div>

            <details class="relative" @if ($selectedFeatures) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border px-3 py-2 text-[13px] transition-colors
                                {{ $selectedFeatures ? 'border-brand-600 bg-brand-50 font-semibold text-brand-800' : 'border-slate-300 bg-white text-slate-700' }}">
                    Amenidades
                    @if ($selectedFeatures)
                        <span class="rounded-full bg-brand-700 px-1.5 text-[11px] font-semibold text-white">{{ count($selectedFeatures) }}</span>
                    @else
                        <i class="bi bi-chevron-down text-[10px]"></i>
                    @endif
                </summary>

                <div class="absolute left-0 top-full z-40 mt-2 max-h-96 w-[34rem] overflow-y-auto rounded-xl border border-slate-200 bg-white p-4 shadow-xl">
                    @foreach (\App\Models\Feature::GROUPS as $group => $groupLabel)
                        @if ($features->has($group))
                            <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400 first:mt-0">{{ $groupLabel }}</p>
                            <div class="grid gap-1.5 sm:grid-cols-2">
                                @foreach ($features[$group] as $feature)
                                    <label class="flex items-center gap-2 text-[13px] text-slate-700">
                                        <input type="checkbox" name="features[]" value="{{ $feature->id }}"
                                               @checked(in_array($feature->id, $selectedFeatures, true))
                                               class="rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                                        <span>{{ $feature->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                    <button class="mt-4 w-full rounded-lg bg-brand-800 py-2 text-[13px] font-semibold text-white hover:bg-brand-900">
                        Aplicar amenidades
                    </button>
                </div>
            </details>

            <button class="rounded-lg bg-brand-800 px-4 py-2 text-[13px] font-semibold text-white transition-colors hover:bg-brand-900">
                Buscar
            </button>
        </div>

        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-2 border-t border-slate-100 px-5 py-2">
            @forelse ($activeFilters as $filter)
                <a href="{{ route('public.properties.index', $filter['query']) }}"
                   class="inline-flex items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-[12px] font-medium text-brand-800 transition-colors hover:bg-brand-100">
                    {{ $filter['label'] }}
                    <i class="bi bi-x-lg text-[9px]"></i>
                </a>
            @empty
                <span class="text-[12.5px] text-slate-400">Sin filtros aplicados</span>
            @endforelse

            @if ($activeFilters)
                <a href="{{ route('public.properties.index') }}" class="text-[12.5px] text-slate-500 underline hover:text-brand-700">
                    Limpiar todo
                </a>
            @endif

            <div class="ml-auto flex items-center gap-3">
                <span class="text-[12.5px] text-slate-600"><b class="font-semibold text-slate-900">{{ $total }}</b> resultados</span>

                <select name="sort" class="{{ $field }} py-1.5" data-autosubmit>
                    @foreach (\App\Http\Controllers\PublicPropertyController::SORTS as $value => $text)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $text }}</option>
                    @endforeach
                </select>

                <a href="{{ route('public.properties.index', array_merge(request()->except(['view', 'page', 'bounds']), $showMap ? ['view' => 'grid'] : [])) }}"
                   class="hidden items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[12.5px] transition-colors lg:inline-flex
                          {{ $showMap ? 'border-brand-600 bg-brand-50 font-semibold text-brand-800' : 'border-slate-300 bg-white text-slate-600' }}">
                    <i class="bi bi-map"></i> Mapa
                </a>
            </div>
        </div>
    </form>

    @if ($properties->isEmpty())
        <div class="mx-auto max-w-[1600px] px-5 py-24 text-center">
            <i class="bi bi-search text-4xl text-slate-300"></i>
            <p class="mt-3 text-sm text-slate-500">No encontramos inmuebles con esos criterios.</p>
            <a href="{{ route('public.properties.index') }}" class="mt-4 inline-block text-sm font-medium text-brand-700 hover:underline">
                Quitar los filtros
            </a>
        </div>
    @elseif ($showMap)
        <div class="mx-auto flex max-w-[1600px] items-start">
            <div class="min-w-0 flex-1 divide-y divide-slate-100 lg:max-w-[54%]">
                @foreach ($properties as $property)
                    @include('public._row', ['property' => $property])
                @endforeach

                <div class="px-4 py-6">{{ $properties->links() }}</div>
            </div>

            <div class="sticky relative hidden flex-1 border-l border-slate-200 lg:block
                        top-[calc(4.5rem+var(--catalog-bar,6rem))] h-[calc(100vh-4.5rem-var(--catalog-bar,6rem))]">
                <div id="catalogMap" class="h-full w-full bg-slate-100"></div>

                @if (! empty($mapPoints))
                    <button type="button" data-map-search
                            class="absolute right-4 top-4 z-[500] rounded-lg bg-white px-3 py-2 text-[12.5px] font-semibold text-slate-800 shadow-md transition-colors hover:bg-slate-50">
                        <i class="bi bi-arrow-repeat"></i> Buscar en esta zona
                    </button>
                @else
                    <p class="absolute inset-x-8 top-1/2 z-[500] -translate-y-1/2 rounded-xl bg-white/95 px-4 py-3 text-center text-[13px] text-slate-500 shadow">
                        Ninguno de estos inmuebles tiene ubicación en el mapa todavía.
                    </p>
                @endif
            </div>
        </div>
    @else
        <div class="mx-auto max-w-[1600px] px-5 py-8">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($properties as $property)
                    @include('public._card', ['property' => $property])
                @endforeach
            </div>
            <div class="mt-8">{{ $properties->links() }}</div>
        </div>
    @endif

    <script type="application/json" data-catalog-points>@json($mapPoints)</script>
@endif
@endsection

@push('scripts')
    @vite('resources/js/map.js')
@endpush

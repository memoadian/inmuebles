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
@endphp

@section('title', $seoTitle)
@section('meta_description', $metaDescription)

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700">
        <div class="pointer-events-none absolute -top-24 -right-24 h-80 w-80 rounded-full bg-accent-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-16 h-72 w-72 rounded-full bg-brand-400/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 pt-14 pb-24 md:pt-20 md:pb-28 text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-100 ring-1 ring-inset ring-white/15">
                <i class="bi bi-stars"></i> Publicado directamente por dueños y agentes
            </span>

            <h1 class="mt-5 font-serif text-4xl md:text-5xl font-semibold tracking-tight text-white text-balance">
                Encuentra el inmueble ideal para ti
            </h1>
            <p class="mt-4 text-base md:text-lg text-brand-100/90 max-w-xl mx-auto text-balance">
                Casas, departamentos y terrenos en venta o renta, en las zonas donde quieres vivir.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4">
        <form method="GET" action="{{ route('public.properties.index') }}"
              class="relative -mt-14 md:-mt-16 bg-white rounded-2xl border border-stone-200 shadow-xl shadow-brand-950/10 p-4 md:p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="¿Qué estás buscando?"
                       class="rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm lg:col-span-2
                              focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15 outline-none transition-shadow">

                <select name="type" class="rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm text-stone-700
                                            focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15 outline-none">
                    <option value="">Todos los tipos</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>

                <select name="operation" class="rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm text-stone-700
                                                 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15 outline-none">
                    <option value="">Venta y renta</option>
                    <option value="sale" @selected(request('operation') === 'sale')>Venta</option>
                    <option value="rent" @selected(request('operation') === 'rent')>Renta</option>
                </select>

                <select name="state" class="rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm text-stone-700
                                             focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15 outline-none">
                    <option value="">Todo el país</option>
                    @foreach ($states as $state)
                        <option value="{{ $state->id }}" @selected(request('state') == $state->id)>{{ $state->name }}</option>
                    @endforeach
                </select>

                <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Precio mínimo"
                       class="rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm
                              focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15 outline-none">
                <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Precio máximo"
                       class="rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm
                              focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15 outline-none">

                <select name="bedrooms" class="rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm text-stone-700
                                                focus:border-brand-600 focus:ring-2 focus:ring-brand-600/15 outline-none">
                    <option value="">Recámaras</option>
                    @foreach ([1, 2, 3, 4] as $n)
                        <option value="{{ $n }}" @selected(request('bedrooms') == $n)>{{ $n }}+</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 flex items-center gap-4">
                <button class="inline-flex items-center gap-2 rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-medium text-white
                               shadow-sm hover:bg-brand-800 transition-colors">
                    <i class="bi bi-search"></i> Buscar
                </button>
                @if (request()->hasAny(['q', 'type', 'operation', 'state', 'min_price', 'max_price', 'bedrooms']))
                    <a href="{{ route('public.properties.index') }}" class="text-sm text-stone-500 hover:text-brand-700 hover:underline">
                        Limpiar filtros
                    </a>
                @endif
                <span class="ml-auto text-sm text-stone-500">{{ $properties->total() }} resultados</span>
            </div>
        </form>

        <div class="pt-10 pb-16">
            @if ($properties->isEmpty())
                <div class="bg-white rounded-2xl border border-stone-200 px-4 py-20 text-center">
                    <i class="bi bi-search text-4xl text-stone-300"></i>
                    <p class="mt-3 text-sm text-stone-500">No encontramos inmuebles con esos criterios.</p>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($properties as $property)
                        <a href="{{ route('public.properties.show', $property->slug) }}"
                           class="group bg-white rounded-2xl border border-stone-200 overflow-hidden
                                  hover:shadow-lg hover:shadow-stone-300/40 hover:-translate-y-0.5 transition-all duration-200">
                            <div class="aspect-[4/3] bg-stone-100 relative">
                                @if ($property->cover)
                                    <img src="{{ $property->cover->thumb_url }}" alt="{{ $property->title }}"
                                         class="h-full w-full object-cover">
                                @else
                                    <div class="h-full w-full flex items-center justify-center">
                                        <i class="bi bi-image text-3xl text-stone-300"></i>
                                    </div>
                                @endif

                                <div class="absolute top-3 left-3 flex gap-1.5">
                                    <span class="rounded-full bg-white/95 backdrop-blur px-2.5 py-1 text-xs font-medium text-brand-800 shadow-sm">
                                        {{ $property->operation === 'rent' ? 'Renta' : 'Venta' }}
                                    </span>
                                    @if ($property->is_featured)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-accent-500 px-2.5 py-1 text-xs font-medium text-white shadow-sm">
                                            <i class="bi bi-stars"></i> Destacada
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-4">
                                <p class="font-serif text-xl font-semibold text-brand-900">
                                    ${{ number_format($property->price, 0) }}
                                    <span class="font-sans text-sm font-normal text-stone-500">
                                        {{ $property->currency }}{{ $property->operation === 'rent' ? ' / mes' : '' }}
                                    </span>
                                </p>

                                <h3 class="mt-1 font-medium text-stone-800 line-clamp-2 group-hover:text-brand-700 transition-colors">
                                    {{ $property->title }}
                                </h3>

                                <p class="mt-1.5 flex items-center gap-1 text-sm text-stone-500">
                                    <i class="bi bi-geo-alt"></i>
                                    {{ $property->city?->name }}{{ $property->state ? ', '.$property->state->name : '' }}
                                </p>

                                <div class="mt-3 pt-3 border-t border-stone-100 flex gap-3 text-xs text-stone-600">
                                    <span class="inline-flex items-center gap-1"><i class="bi bi-door-closed text-stone-400"></i> {{ $property->bedrooms }}</span>
                                    <span class="inline-flex items-center gap-1"><i class="bi bi-droplet text-stone-400"></i> {{ $property->bathrooms }}</span>
                                    @if ($property->built_area)
                                        <span class="inline-flex items-center gap-1"><i class="bi bi-rulers text-stone-400"></i> {{ (int) $property->built_area }} m²</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">{{ $properties->links() }}</div>
            @endif
        </div>
    </div>
@endsection

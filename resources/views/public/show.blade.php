@extends('layouts.public')

@section('title', $property->title)

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-6">
        <a href="{{ route('public.properties.index') }}"
           class="inline-flex items-center gap-1.5 mb-5 text-sm text-stone-600 hover:text-brand-700">
            <i class="bi bi-arrow-left"></i> Volver al catálogo
        </a>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                @if ($property->images->isNotEmpty())
                    <div class="rounded-2xl overflow-hidden border border-stone-200 bg-white">
                        <div class="relative">
                            <img src="{{ $property->images->first()->url }}" alt="{{ $property->title }}"
                                 class="w-full aspect-[16/10] object-cover">
                            <span class="absolute top-4 left-4 rounded-full bg-white/95 backdrop-blur px-3 py-1 text-xs font-medium text-brand-800 shadow-sm">
                                {{ $property->operation === 'rent' ? 'En renta' : 'En venta' }}
                            </span>
                        </div>

                        @if ($property->images->count() > 1)
                            <div class="grid grid-cols-4 gap-1 p-1">
                                @foreach ($property->images->skip(1)->take(4) as $image)
                                    <a href="{{ $image->url }}" target="_blank" class="block overflow-hidden rounded-lg">
                                        <img src="{{ $image->thumb_url }}" alt=""
                                             class="aspect-[4/3] w-full object-cover hover:opacity-90 transition-opacity">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <section class="bg-white rounded-2xl border border-stone-200 p-5 md:p-6">
                    <h1 class="font-serif text-2xl md:text-3xl font-semibold text-brand-950">{{ $property->title }}</h1>
                    <p class="mt-1.5 flex items-center gap-1.5 text-stone-500">
                        <i class="bi bi-geo-alt"></i> {{ $property->full_address }}
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-50 border border-stone-200 px-3 py-1.5 text-sm text-stone-700">
                            <i class="bi bi-door-closed text-brand-600"></i> {{ $property->bedrooms }} recámaras
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-50 border border-stone-200 px-3 py-1.5 text-sm text-stone-700">
                            <i class="bi bi-droplet text-brand-600"></i> {{ $property->bathrooms }} baños
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-50 border border-stone-200 px-3 py-1.5 text-sm text-stone-700">
                            <i class="bi bi-car-front text-brand-600"></i> {{ $property->parking_spaces }} estacionamientos
                        </span>
                        @if ($property->built_area)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-50 border border-stone-200 px-3 py-1.5 text-sm text-stone-700">
                                <i class="bi bi-rulers text-brand-600"></i> {{ (int) $property->built_area }} m² construidos
                            </span>
                        @endif
                        @if ($property->land_area)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-50 border border-stone-200 px-3 py-1.5 text-sm text-stone-700">
                                <i class="bi bi-map text-brand-600"></i> {{ (int) $property->land_area }} m² de terreno
                            </span>
                        @endif
                    </div>

                    @if ($property->description)
                        <p class="mt-5 whitespace-pre-line leading-relaxed text-stone-700">{{ $property->description }}</p>
                    @endif
                </section>

                @if ($property->features->isNotEmpty())
                    <section class="bg-white rounded-2xl border border-stone-200 p-5 md:p-6">
                        <h2 class="font-serif text-lg font-semibold text-brand-950 mb-4">Amenidades</h2>
                        <div class="grid gap-3 sm:grid-cols-3 text-sm text-stone-700">
                            @foreach ($property->features as $feature)
                                <span class="inline-flex items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-brand-600"></i> {{ $feature->name }}
                                </span>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <div class="space-y-6">
                <section class="bg-white rounded-2xl border border-stone-200 p-5 md:p-6 lg:sticky lg:top-24">
                    <p class="text-sm font-medium text-brand-700">
                        {{ ['sale' => 'En venta', 'rent' => 'En renta', 'both' => 'Venta o renta'][$property->operation] }}
                    </p>
                    <p class="mt-1 font-serif text-3xl font-semibold text-brand-950">
                        ${{ number_format($property->price, 0) }}
                        <span class="font-sans text-lg font-normal text-stone-500">{{ $property->currency }}</span>
                    </p>
                    @if ($property->maintenance_fee)
                        <p class="mt-1 text-sm text-stone-500">
                            + ${{ number_format($property->maintenance_fee, 0) }} de mantenimiento
                        </p>
                    @endif

                    <div class="mt-5 pt-5 border-t border-stone-100">
                        <p class="text-sm text-stone-500">Publicado por</p>
                        <p class="font-medium text-stone-800">{{ $property->user->name }}</p>

                        @if ($property->user->phone)
                            <a href="tel:{{ $property->user->phone }}"
                               class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5
                                      text-sm font-medium text-white shadow-sm hover:bg-brand-800 transition-colors">
                                <i class="bi bi-telephone"></i> {{ $property->user->phone }}
                            </a>
                        @endif

                        <a href="mailto:{{ $property->user->email }}?subject={{ rawurlencode('Interés en: '.$property->title) }}"
                           class="mt-2 flex items-center justify-center gap-2 rounded-xl border border-stone-300 px-4 py-2.5
                                  text-sm font-medium text-stone-700 hover:bg-stone-50 transition-colors">
                            <i class="bi bi-envelope"></i> Enviar correo
                        </a>
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
                           class="group bg-white rounded-2xl border border-stone-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                            <div class="aspect-[4/3] bg-stone-100">
                                @if ($item->cover)
                                    <img src="{{ $item->cover->thumb_url }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <div class="p-3.5">
                                <p class="font-serif font-semibold text-brand-900">${{ number_format($item->price, 0) }}</p>
                                <p class="text-sm text-stone-600 line-clamp-1 group-hover:text-brand-700 transition-colors">{{ $item->title }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

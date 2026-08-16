@extends('layouts.app')

@section('title', $property->title)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <x-property-status :status="$property->status" />
            <span class="text-sm text-slate-500">{{ $property->type?->name }}</span>
        </div>

        @can('update', $property)
            <a href="{{ route('properties.edit', $property) }}"
               class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <i class="bi bi-pencil"></i> Editar
            </a>
        @endcan
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
                <section class="bg-white rounded-xl border border-slate-200 p-4">
                    <h2 class="font-medium text-slate-800 mb-3">Amenidades</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($property->features as $feature)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
                                {{ $feature->name }}
                            </span>
                        @endforeach
                    </div>
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
            </section>

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
                        'Antigüedad' => $property->age_years ? $property->age_years.' años' : null,
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

@extends('layouts.app')

@section('title', 'Propiedades')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por título o calle…"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-56
                          focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">

            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Todos los tipos</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(request('type') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Cualquier estado</option>
                @foreach (['draft' => 'Borrador', 'published' => 'Publicada', 'reserved' => 'Apartada', 'sold' => 'Vendida', 'rented' => 'Rentada', 'inactive' => 'Inactiva'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">
                <i class="bi bi-search"></i>
            </button>

            @if (request()->hasAny(['search', 'type', 'status']))
                <a href="{{ route('properties.index') }}" class="text-sm text-slate-500 hover:underline">Limpiar</a>
            @endif
        </form>

        @can('properties.create')
            <a href="{{ route('properties.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <i class="bi bi-plus-lg"></i>
                <span>Nueva propiedad</span>
            </a>
        @endcan
    </div>

    @if ($properties->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-16 text-center">
            <i class="bi bi-house-add text-4xl text-slate-300"></i>
            <p class="mt-2 text-sm text-slate-500">No se encontraron propiedades.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($properties as $property)
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden flex flex-col">
                    <div class="aspect-[4/3] bg-slate-100">
                        @if ($property->cover)
                            <img src="{{ $property->cover->thumb_url }}" alt="{{ $property->title }}"
                                 class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center">
                                <i class="bi bi-image text-3xl text-slate-300"></i>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 flex-1 flex flex-col">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-medium text-slate-900 line-clamp-2">{{ $property->title }}</h3>
                            <x-property-status :status="$property->status" />
                        </div>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $property->type?->name }}
                            @if ($property->city) &middot; {{ $property->city->name }} @endif
                        </p>

                        <p class="mt-2 text-lg font-semibold text-slate-900">
                            ${{ number_format($property->price, 0) }}
                            <span class="text-sm font-normal text-slate-500">{{ $property->currency }}</span>
                        </p>

                        <div class="mt-2 flex gap-3 text-xs text-slate-500">
                            <span><i class="bi bi-door-closed"></i> {{ $property->bedrooms }}</span>
                            <span><i class="bi bi-droplet"></i> {{ $property->bathrooms }}</span>
                            <span><i class="bi bi-car-front"></i> {{ $property->parking_spaces }}</span>
                            @if ($property->built_area)
                                <span><i class="bi bi-rulers"></i> {{ (int) $property->built_area }} m²</span>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                            <a href="{{ route('properties.edit', $property) }}"
                               class="flex-1 text-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">
                                Editar
                            </a>

                            @can('publish', $property)
                                <form method="POST" action="{{ route('properties.publish', $property) }}" class="flex-1">
                                    @csrf
                                    <button class="w-full rounded-lg px-3 py-1.5 text-sm text-white
                                                   {{ $property->isPublished() ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                        {{ $property->isPublished() ? 'Despublicar' : 'Publicar' }}
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $properties->links() }}
        </div>
    @endif
@endsection

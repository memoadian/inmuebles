@extends('layouts.app')

@section('title', 'Editar propiedad')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <x-property-status :status="$property->status" />
            @if ($property->isPublished())
                <a href="{{ route('public.properties.show', $property->slug) }}" target="_blank"
                   class="text-sm text-slate-600 hover:underline">
                    <i class="bi bi-box-arrow-up-right"></i> Ver publicada
                </a>
            @endif
        </div>

        @can('delete', $property)
            <form method="POST" action="{{ route('properties.destroy', $property) }}"
                  onsubmit="return confirm('¿Eliminar esta propiedad? Se podrá restaurar desde la base de datos.')">
                @csrf
                @method('DELETE')
                <button class="rounded-lg border border-red-300 bg-white px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </form>
        @endcan
    </div>

    <form method="POST" action="{{ route('properties.update', $property) }}">
        @csrf
        @method('PUT')
        @include('properties._form')
    </form>

    {{-- La galería vive fuera del form principal: sube por su propio endpoint --}}
    @include('properties._images')
@endsection

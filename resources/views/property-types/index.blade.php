@extends('layouts.app')

@section('title', 'Tipos de inmueble')

@section('content')
    <div class="flex items-center justify-end mb-4">
        <a href="{{ route('property-types.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
            <i class="bi bi-plus-lg"></i> Nuevo tipo
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Nombre</th>
                    <th class="px-4 py-3 font-medium">Slug</th>
                    <th class="px-4 py-3 font-medium">Propiedades</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($types as $type)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $type->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $type->slug }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $type->properties_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $type->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('property-types.edit', $type) }}"
                               class="text-slate-600 hover:text-slate-900"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('property-types.destroy', $type) }}" class="inline"
                                  onsubmit="return confirm('¿Eliminar este tipo?')">
                                @csrf
                                @method('DELETE')
                                <button class="ml-2 text-red-600 hover:text-red-700"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">Sin tipos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

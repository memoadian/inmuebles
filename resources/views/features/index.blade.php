@extends('layouts.app')

@section('title', 'Amenidades')

@section('content')
    <div class="flex items-center justify-end mb-4">
        <a href="{{ route('features.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
            <i class="bi bi-plus-lg"></i> Nueva amenidad
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
                @forelse ($features as $feature)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $feature->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $feature->slug }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $feature->properties_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $feature->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $feature->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('features.edit', $feature) }}"
                               class="text-slate-600 hover:text-slate-900"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('features.destroy', $feature) }}" class="inline"
                                  onsubmit="return confirm('¿Eliminar esta amenidad?')">
                                @csrf
                                @method('DELETE')
                                <button class="ml-2 text-red-600 hover:text-red-700"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">Sin amenidades registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

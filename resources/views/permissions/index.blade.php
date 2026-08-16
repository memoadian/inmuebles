@extends('layouts.app')

@section('title', 'Permisos')

@section('content')
    @php
        $labels = [
            'properties' => 'Propiedades',
            'images' => 'Fotos',
            'catalogs' => 'Catálogos',
            'users' => 'Usuarios',
            'roles' => 'Roles',
            'permissions' => 'Permisos',
        ];
    @endphp

    <div class="flex items-center justify-end mb-4">
        @can('permissions.create')
            <a href="{{ route('permissions.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <i class="bi bi-plus-lg"></i> Nuevo permiso
            </a>
        @endcan
    </div>

    <div class="space-y-4">
        @foreach ($groups as $group => $permissions)
            <section class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <h3 class="px-4 py-3 bg-slate-50 border-b border-slate-200 font-medium text-slate-800">
                    {{ $labels[$group] ?? ucfirst($group ?? 'Sin grupo') }}
                </h3>

                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($permissions as $permission)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700 w-56">{{ $permission->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $permission->description ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                    {{ $permission->roles_count }} {{ Str::plural('rol', $permission->roles_count) }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @can('permissions.edit')
                                        <a href="{{ route('permissions.edit', $permission) }}"
                                           class="text-slate-600 hover:text-slate-900"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('permissions.delete')
                                        <form method="POST" action="{{ route('permissions.destroy', $permission) }}"
                                              class="inline" onsubmit="return confirm('¿Eliminar este permiso?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-2 text-red-600 hover:text-red-700"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endforeach
    </div>
@endsection

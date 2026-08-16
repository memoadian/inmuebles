@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="flex items-center justify-end mb-4">
        @can('roles.create')
            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <i class="bi bi-plus-lg"></i> Nuevo rol
            </a>
        @endcan
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Rol</th>
                    <th class="px-4 py-3 font-medium">Descripción</th>
                    <th class="px-4 py-3 font-medium">Permisos</th>
                    <th class="px-4 py-3 font-medium">Usuarios</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('roles.show', $role) }}" class="text-slate-600 hover:text-slate-900">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('roles.edit')
                                <a href="{{ route('roles.edit', $role) }}" class="ml-2 text-slate-600 hover:text-slate-900">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endcan
                            @can('roles.delete')
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar este rol?')">
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
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o correo…"
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-64
                          focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
            <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">
                <i class="bi bi-search"></i>
            </button>
        </form>

        @can('users.create')
            <a href="{{ route('users.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <i class="bi bi-plus-lg"></i> Nuevo usuario
            </a>
        @endcan
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Nombre</th>
                    <th class="px-4 py-3 font-medium">Correo</th>
                    <th class="px-4 py-3 font-medium">Roles</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @foreach ($user->roles as $role)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('users.show', $user) }}" class="text-slate-600 hover:text-slate-900">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('users.edit')
                                <a href="{{ route('users.edit', $user) }}" class="ml-2 text-slate-600 hover:text-slate-900">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endcan
                            @can('users.delete')
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar este usuario?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="ml-2 text-red-600 hover:text-red-700"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-500">Sin usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection

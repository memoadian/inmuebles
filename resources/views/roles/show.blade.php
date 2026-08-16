@extends('layouts.app')

@section('title', 'Rol: '.$role->name)

@section('content')
    <div class="max-w-3xl space-y-6">
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $role->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $role->description ?? 'Sin descripción' }}</p>
                </div>
                @can('roles.edit')
                    <a href="{{ route('roles.edit', $role) }}"
                       class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                @endcan
            </div>
        </section>

        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="font-medium text-slate-800 mb-3">Permisos ({{ $role->permissions->count() }})</h3>
            <div class="flex flex-wrap gap-2">
                @forelse ($role->permissions as $permission)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-700">
                        {{ $permission->name }}
                    </span>
                @empty
                    <p class="text-sm text-slate-500">Este rol no tiene permisos asignados.</p>
                @endforelse
            </div>
        </section>

        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="font-medium text-slate-800 mb-3">Usuarios ({{ $role->users->count() }})</h3>
            @if ($role->users->isEmpty())
                <p class="text-sm text-slate-500">Ningún usuario tiene este rol.</p>
            @else
                <ul class="divide-y divide-slate-100 -mx-4">
                    @foreach ($role->users as $user)
                        <li class="px-4 py-2 text-sm">
                            <span class="text-slate-800">{{ $user->name }}</span>
                            <span class="text-slate-500">— {{ $user->email }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection

@extends('layouts.app')

@section('title', $user->name)

@section('content')
    <div class="max-w-2xl space-y-6">
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    @if ($user->phone)
                        <p class="text-sm text-slate-500">{{ $user->phone }}</p>
                    @endif
                </div>

                @can('users.edit')
                    <a href="{{ route('users.edit', $user) }}"
                       class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                @endcan
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($user->roles as $role)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700">{{ $role->name }}</span>
                @endforeach
                <span class="rounded-full px-2.5 py-1 text-xs font-medium
                    {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-slate-200">
            <h3 class="px-4 py-3 border-b border-slate-200 font-medium text-slate-800">
                Propiedades ({{ $user->properties->count() }})
            </h3>

            @if ($user->properties->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-slate-500">Este usuario no tiene propiedades.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($user->properties as $property)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <a href="{{ route('properties.edit', $property) }}"
                               class="text-sm text-slate-800 hover:underline truncate">{{ $property->title }}</a>
                            <x-property-status :status="$property->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection

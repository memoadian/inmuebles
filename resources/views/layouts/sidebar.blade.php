@php
    $link = 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors';
    $idle = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    $active = 'bg-slate-900 text-white hover:bg-slate-900';
@endphp

<div class="h-full flex flex-col">
    <div class="h-14 flex items-center gap-2 px-4 border-b border-slate-200 shrink-0">
        <i class="bi bi-houses-fill text-xl text-slate-900"></i>
        <span class="font-semibold text-slate-900">{{ config('app.name', 'Inmuebles') }}</span>
    </div>

    <nav class="flex-1 overflow-y-auto p-3 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="{{ $link }} {{ request()->routeIs('dashboard') ? $active : $idle }}">
            <i class="bi bi-speedometer2"></i>
            <span>Panel</span>
        </a>

        @can('properties.view')
            <a href="{{ route('properties.index') }}"
               class="{{ $link }} {{ request()->routeIs('properties.*') ? $active : $idle }}">
                <i class="bi bi-houses"></i>
                <span>Propiedades</span>
            </a>
        @endcan

        @canany(['catalogs.manage', 'users.view', 'roles.view'])
            <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                Administración
            </p>
        @endcanany

        @can('catalogs.manage')
            <a href="{{ route('property-types.index') }}"
               class="{{ $link }} {{ request()->routeIs('property-types.*') ? $active : $idle }}">
                <i class="bi bi-tags"></i>
                <span>Tipos de inmueble</span>
            </a>
            <a href="{{ route('features.index') }}"
               class="{{ $link }} {{ request()->routeIs('features.*') ? $active : $idle }}">
                <i class="bi bi-stars"></i>
                <span>Amenidades</span>
            </a>
        @endcan

        @can('users.view')
            <a href="{{ route('users.index') }}"
               class="{{ $link }} {{ request()->routeIs('users.*') ? $active : $idle }}">
                <i class="bi bi-people"></i>
                <span>Usuarios</span>
            </a>
        @endcan

        @can('roles.view')
            <a href="{{ route('roles.index') }}"
               class="{{ $link }} {{ request()->routeIs('roles.*') ? $active : $idle }}">
                <i class="bi bi-shield-lock"></i>
                <span>Roles</span>
            </a>
        @endcan

        @can('permissions.view')
            <a href="{{ route('permissions.index') }}"
               class="{{ $link }} {{ request()->routeIs('permissions.*') ? $active : $idle }}">
                <i class="bi bi-key"></i>
                <span>Permisos</span>
            </a>
        @endcan
    </nav>

    <div class="p-3 border-t border-slate-200 shrink-0">
        <p class="text-xs text-slate-400 px-3">v1.0 &middot; {{ config('app.env') }}</p>
    </div>
</div>

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none';
    $role = $role ?? null;
    $current = $role?->permissions->pluck('name')->all() ?? [];
    $labels = [
        'properties' => 'Propiedades',
        'images' => 'Fotos',
        'catalogs' => 'Catálogos',
        'users' => 'Usuarios',
        'roles' => 'Roles',
        'permissions' => 'Permisos',
    ];
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-4 space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre del rol</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $role?->name) }}" class="{{ $input }}">
        </div>
        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
            <input id="description" name="description" type="text"
                   value="{{ old('description', $role?->description) }}" class="{{ $input }}">
        </div>
    </div>
</div>

<div class="mt-4 space-y-4">
    @foreach ($groups as $group => $permissions)
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="font-medium text-slate-800 mb-3">{{ $labels[$group] ?? ucfirst($group ?? 'Otros') }}</h3>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($permissions as $permission)
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                               @checked(in_array($permission->name, old('permissions', $current)))
                               class="mt-0.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span>
                            <span class="font-mono text-xs text-slate-500">{{ $permission->name }}</span>
                            @if ($permission->description)
                                <span class="block text-slate-600">{{ $permission->description }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

<div class="mt-4 flex gap-2">
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        Guardar
    </button>
    <a href="{{ route('roles.index') }}"
       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Cancelar</a>
</div>

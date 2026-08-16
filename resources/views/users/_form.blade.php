@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none';
    $user = $user ?? null;
    $current = $user?->roles->pluck('name')->all() ?? [];
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-4 space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $user?->name) }}" class="{{ $input }}">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo</label>
            <input id="email" name="email" type="email" required value="{{ old('email', $user?->email) }}" class="{{ $input }}">
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $user?->phone) }}" class="{{ $input }}">
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                Contraseña
                @if ($user)<span class="text-slate-400 font-normal">(dejar vacío para conservarla)</span>@endif
            </label>
            <input id="password" name="password" type="password" @required(! $user) class="{{ $input }}">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" @required(! $user) class="{{ $input }}">
        </div>
    </div>

    <div>
        <p class="block text-sm font-medium text-slate-700 mb-2">Roles</p>
        <div class="flex flex-wrap gap-3">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                           @checked(in_array($role->name, old('roles', $current)))
                           class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span>{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))
               class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
        <span>Cuenta activa</span>
    </label>
</div>

<div class="mt-4 flex gap-2">
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        Guardar
    </button>
    <a href="{{ route('users.index') }}"
       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Cancelar</a>
</div>

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none';
    $permission = $permission ?? null;
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-4 space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $permission?->name) }}"
               placeholder="properties.export" class="{{ $input }} font-mono">
        <p class="mt-1 text-xs text-slate-500">Convención: <span class="font-mono">grupo.acción</span></p>
    </div>

    <div>
        <label for="group" class="block text-sm font-medium text-slate-700 mb-1">Grupo</label>
        <input id="group" name="group" type="text" required list="groups"
               value="{{ old('group', $permission?->group) }}" class="{{ $input }}">
        <datalist id="groups">
            @foreach ($groups as $group)
                <option value="{{ $group }}"></option>
            @endforeach
        </datalist>
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
        <input id="description" name="description" type="text"
               value="{{ old('description', $permission?->description) }}" class="{{ $input }}">
    </div>
</div>

<div class="mt-4 flex gap-2">
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        Guardar
    </button>
    <a href="{{ route('permissions.index') }}"
       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Cancelar</a>
</div>

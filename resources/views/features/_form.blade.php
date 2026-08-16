@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none';
    $feature = $feature ?? null;
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-4 space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $feature?->name) }}" class="{{ $input }}">
    </div>

    <div>
        <label for="icon" class="block text-sm font-medium text-slate-700 mb-1">
            Icono <span class="text-slate-400 font-normal">(nombre de Bootstrap Icons, opcional)</span>
        </label>
        <input id="icon" name="icon" type="text" value="{{ old('icon', $feature?->icon) }}"
               placeholder="water" class="{{ $input }}">
    </div>

    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $feature?->is_active ?? true))
               class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
        <span>Activa</span>
    </label>
</div>

<div class="mt-4 flex gap-2">
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        Guardar
    </button>
    <a href="{{ route('features.index') }}"
       class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Cancelar</a>
</div>

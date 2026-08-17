@php
    /** @var \App\Models\Property|null $property */
    $property = $property ?? null;
    $selectedFeatures = $property?->features->pluck('id')->all() ?? [];
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none';
    $label = 'block text-sm font-medium text-slate-700 mb-1';
@endphp

<section class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
    <h2 class="font-medium text-slate-800 mb-1 flex items-center gap-2">
        <i class="bi bi-stars text-slate-500"></i> Autocompletar con IA
    </h2>
    <p class="text-xs text-slate-500 mb-3">
        Pega la descripción que ya tengas (WhatsApp, un anuncio anterior, tus notas) y la IA intenta llenar los campos de abajo. Siempre revisa el resultado antes de guardar.
    </p>
    <textarea id="aiExtractText" rows="4" class="{{ $input }}"
              placeholder="Ej. Casa de 3 recámaras y 2 baños en Coyoacán, CDMX, 180 m² de terreno, $5,200,000..."></textarea>
    <div class="mt-2 flex items-center gap-3">
        <button type="button" id="aiExtractBtn" data-url="{{ route('properties.ai-extract') }}"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50">
            <i class="bi bi-stars"></i> Autocompletar
        </button>
        <span id="aiExtractStatus" class="text-xs text-slate-500"></span>
    </div>
</section>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        {{-- Datos generales --}}
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-medium text-slate-800 mb-4">Datos generales</h2>

            <div class="space-y-4">
                <div>
                    <label for="title" class="{{ $label }}">Título</label>
                    <input id="title" name="title" type="text" required
                           value="{{ old('title', $property?->title) }}"
                           placeholder="Casa en venta en Del Valle con jardín"
                           class="{{ $input }}">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="property_type_id" class="{{ $label }}">Tipo de inmueble</label>
                        <select id="property_type_id" name="property_type_id" required class="{{ $input }}">
                            <option value="">Selecciona…</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}"
                                    @selected(old('property_type_id', $property?->property_type_id) == $type->id)>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="operation" class="{{ $label }}">Operación</label>
                        <select id="operation" name="operation" required class="{{ $input }}">
                            @foreach (['sale' => 'Venta', 'rent' => 'Renta', 'both' => 'Venta o renta'] as $value => $text)
                                <option value="{{ $value }}" @selected(old('operation', $property?->operation) === $value)>
                                    {{ $text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="{{ $label }}">Descripción</label>
                    <textarea id="description" name="description" rows="5" class="{{ $input }}"
                              placeholder="Describe el inmueble, su entorno y lo que lo hace atractivo.">{{ old('description', $property?->description) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Características --}}
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-medium text-slate-800 mb-4">Características</h2>

            <div class="grid gap-4 sm:grid-cols-4">
                @foreach ([
                    'bedrooms' => 'Recámaras',
                    'bathrooms' => 'Baños',
                    'half_bathrooms' => 'Medios baños',
                    'parking_spaces' => 'Estacionamientos',
                ] as $field => $text)
                    <div>
                        <label for="{{ $field }}" class="{{ $label }}">{{ $text }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" max="99"
                               value="{{ old($field, $property?->$field ?? 0) }}" class="{{ $input }}">
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-4">
                <div>
                    <label for="land_area" class="{{ $label }}">Terreno (m²)</label>
                    <input id="land_area" name="land_area" type="number" step="0.01" min="0"
                           value="{{ old('land_area', $property?->land_area) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="built_area" class="{{ $label }}">Construcción (m²)</label>
                    <input id="built_area" name="built_area" type="number" step="0.01" min="0"
                           value="{{ old('built_area', $property?->built_area) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="floors" class="{{ $label }}">Niveles</label>
                    <input id="floors" name="floors" type="number" min="0"
                           value="{{ old('floors', $property?->floors) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="age_years" class="{{ $label }}">Antigüedad (años)</label>
                    <input id="age_years" name="age_years" type="number" min="0"
                           value="{{ old('age_years', $property?->age_years) }}" class="{{ $input }}">
                </div>
            </div>
        </section>

        {{-- Amenidades --}}
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-medium text-slate-800 mb-4">Amenidades</h2>

            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($features as $feature)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="features[]" value="{{ $feature->id }}"
                               @checked(in_array($feature->id, old('features', $selectedFeatures)))
                               class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span>{{ $feature->name }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- Ubicación --}}
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-medium text-slate-800 mb-4">Ubicación</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="street" class="{{ $label }}">Calle</label>
                    <input id="street" name="street" type="text"
                           value="{{ old('street', $property?->street) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="ext_number" class="{{ $label }}">Número exterior</label>
                    <input id="ext_number" name="ext_number" type="text"
                           value="{{ old('ext_number', $property?->ext_number) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="int_number" class="{{ $label }}">Número interior</label>
                    <input id="int_number" name="int_number" type="text"
                           value="{{ old('int_number', $property?->int_number) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="state_id" class="{{ $label }}">Estado</label>
                    <select id="state_id" name="state_id" class="{{ $input }}">
                        <option value="">Selecciona…</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected(old('state_id', $property?->state_id) == $state->id)>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="postal_code" class="{{ $label }}">Código postal</label>
                    <input id="postal_code" name="postal_code" type="text"
                           value="{{ old('postal_code', $property?->postal_code) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="latitude" class="{{ $label }}">Latitud</label>
                    <input id="latitude" name="latitude" type="number" step="0.0000001"
                           value="{{ old('latitude', $property?->latitude) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="longitude" class="{{ $label }}">Longitud</label>
                    <input id="longitude" name="longitude" type="number" step="0.0000001"
                           value="{{ old('longitude', $property?->longitude) }}" class="{{ $input }}">
                </div>
            </div>
        </section>
    </div>

    {{-- Columna lateral --}}
    <div class="space-y-6">
        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-medium text-slate-800 mb-4">Precio</h2>

            <div class="space-y-4">
                <div>
                    <label for="price" class="{{ $label }}">Precio</label>
                    <input id="price" name="price" type="number" step="0.01" min="0" required
                           value="{{ old('price', $property?->price ?? 0) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="currency" class="{{ $label }}">Moneda</label>
                    <select id="currency" name="currency" required class="{{ $input }}">
                        @foreach (['MXN', 'USD'] as $currency)
                            <option value="{{ $currency }}" @selected(old('currency', $property?->currency ?? 'MXN') === $currency)>
                                {{ $currency }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="maintenance_fee" class="{{ $label }}">Mantenimiento mensual</label>
                    <input id="maintenance_fee" name="maintenance_fee" type="number" step="0.01" min="0"
                           value="{{ old('maintenance_fee', $property?->maintenance_fee) }}" class="{{ $input }}">
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="font-medium text-slate-800 mb-4">Publicación</h2>

            <div class="space-y-4">
                <div>
                    <label for="status" class="{{ $label }}">Estado</label>
                    <select id="status" name="status" required class="{{ $input }}">
                        @foreach ([
                            'draft' => 'Borrador',
                            'published' => 'Publicada',
                            'reserved' => 'Apartada',
                            'sold' => 'Vendida',
                            'rented' => 'Rentada',
                            'inactive' => 'Inactiva',
                        ] as $value => $text)
                            <option value="{{ $value }}" @selected(old('status', $property?->status ?? 'draft') === $value)>
                                {{ $text }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_featured" value="1"
                           @checked(old('is_featured', $property?->is_featured))
                           class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span>Destacar en el catálogo</span>
                </label>
            </div>
        </section>

        <div class="flex flex-col gap-2">
            <button type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                {{ $property ? 'Guardar cambios' : 'Crear propiedad' }}
            </button>
            <a href="{{ route('properties.index') }}"
               class="text-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm hover:bg-slate-50">
                Cancelar
            </a>
        </div>
    </div>
</div>

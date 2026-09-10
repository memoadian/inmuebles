{{-- Tarjeta de portada: foto grande, precio dominante y datos de decisión visibles. --}}
<a href="{{ route('public.properties.show', $property->slug) }}"
   class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition-all duration-200
          hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-300/40">
    <div class="relative aspect-[4/3] bg-slate-100">
        @if ($property->cover)
            <img src="{{ $property->cover->thumb_url }}" alt="{{ $property->title }}" loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <span class="flex h-full w-full items-center justify-center text-slate-300">
                <i class="bi bi-image text-3xl"></i>
            </span>
        @endif

        <span class="absolute left-3 top-3 rounded-full bg-slate-50/95 px-2.5 py-1 text-[11px] font-semibold text-brand-900 backdrop-blur">
            {{ $property->operation === 'rent' ? 'Renta' : 'Venta' }}
        </span>

        @if ($property->is_featured)
            <span class="absolute right-3 top-3 rounded-full bg-accent-600 px-2.5 py-1 text-[11px] font-semibold text-white">
                Destacada
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-baseline gap-1.5">
            <p class="text-[22px] font-bold tracking-tight text-slate-900">${{ number_format($property->price, 0) }}</p>
            <span class="text-xs text-slate-500">{{ $property->operation === 'rent' ? '/ mes' : $property->currency }}</span>
        </div>

        <h3 class="mt-1 line-clamp-2 text-[15px] font-medium text-slate-800 transition-colors group-hover:text-brand-700">
            {{ $property->title }}
        </h3>

        <p class="mt-0.5 truncate text-[13px] text-slate-500">
            {{ collect([$property->city?->name, $property->state?->name])->filter()->implode(', ') ?: $property->type?->name }}
        </p>

        <div class="mt-auto flex gap-4 border-t border-slate-100 pt-3 text-[12.5px] text-slate-600" style="margin-top: 12px;">
            <span>{{ $property->bedrooms }} rec</span>
            <span>{{ $property->bathrooms }} baños</span>
            @if ($property->built_area)
                <span>{{ (int) $property->built_area }} m²</span>
            @endif
        </div>
    </div>
</a>

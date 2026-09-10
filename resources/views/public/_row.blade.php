{{-- Fila del listado de resultados: se sincroniza con el pin del mapa por data-property. --}}
@php
    $specs = collect([
        $property->bedrooms ? $property->bedrooms.' rec' : null,
        $property->bathrooms ? $property->bathrooms.' baños' : null,
        $property->built_area ? (int) $property->built_area.' m²' : null,
        $property->parking_spaces ? $property->parking_spaces.' est.' : null,
        $property->condition_label,
    ])->filter()->take(5);
@endphp

<article data-property="{{ $property->id }}"
         class="group relative flex gap-4 p-4 transition-colors hover:bg-slate-50 data-[active=true]:bg-accent-50">
    <a href="{{ route('public.properties.show', $property->slug) }}"
       class="relative h-30 w-44 shrink-0 overflow-hidden rounded-xl bg-slate-100">
        @if ($property->cover)
            <img src="{{ $property->cover->thumb_url }}" alt="{{ $property->title }}" loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <span class="flex h-full w-full items-center justify-center text-slate-300">
                <i class="bi bi-image text-2xl"></i>
            </span>
        @endif

        @if ($property->is_featured)
            <span class="absolute left-2 top-2 rounded-md bg-accent-600 px-2 py-0.5 text-[11px] font-semibold text-white">
                Destacada
            </span>
        @endif
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex items-baseline gap-2">
            <p class="text-xl font-bold tracking-tight text-slate-900">
                ${{ number_format($property->price, 0) }}
            </p>
            <span class="text-xs text-slate-500">
                {{ $property->currency }}{{ $property->operation === 'rent' ? ' / mes' : '' }}
            </span>
            @if ($property->published_at)
                <span class="ml-auto shrink-0 text-[11px] text-slate-400">
                    Publicada {{ $property->published_at->diffForHumans(short: true) }}
                </span>
            @endif
        </div>

        <h3 class="mt-0.5 truncate font-medium text-slate-800 transition-colors group-hover:text-brand-700">
            <a href="{{ route('public.properties.show', $property->slug) }}" class="after:absolute after:inset-0">
                {{ $property->title }}
            </a>
        </h3>

        <p class="mt-0.5 truncate text-[13px] text-slate-500">
            {{ collect([$property->neighborhood?->name, $property->city?->name, $property->state?->name])->filter()->implode(', ') ?: $property->type?->name }}
        </p>

        @if ($specs->isNotEmpty())
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($specs as $spec)
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11.5px] text-slate-600">{{ $spec }}</span>
                @endforeach
            </div>
        @endif

        <div class="relative z-10 mt-2.5 flex items-center gap-2">
            <a href="{{ route('public.properties.show', $property->slug) }}"
               class="rounded-lg bg-brand-800 px-3 py-1.5 text-[12.5px] font-semibold text-white transition-colors hover:bg-brand-900">
                Ver ficha
            </a>
            <a href="{{ route('public.properties.pdf', $property->slug) }}"
               class="rounded-lg border border-slate-300 px-3 py-1.5 text-[12.5px] text-slate-600 transition-colors hover:bg-white">
                PDF
            </a>
        </div>
    </div>
</article>

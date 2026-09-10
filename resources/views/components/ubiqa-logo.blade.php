@props(['wordmark' => true])

{{-- Marca de Ubiqa reconstruida como SVG: nítida a cualquier tamaño y legible
     sobre fondo claro u oscuro (la palabra hereda el color del texto padre).
     El id del degradado se aleatoriza porque el logo aparece más de una vez
     por página (cabecera y pie) y los ids de SVG no pueden repetirse. --}}
@php $gradId = 'ubiqa-mark-'.\Illuminate\Support\Str::random(6); @endphp

<span {{ $attributes->class('inline-flex items-center gap-2.5') }}>
    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"
         class="h-[1.6em] w-auto shrink-0" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $gradId }}" x1="24" y1="6" x2="24" y2="43" gradientUnits="userSpaceOnUse">
                <stop stop-color="#45F1E1"/>
                <stop offset=".5" stop-color="#2F82FF"/>
                <stop offset="1" stop-color="#1B34E6"/>
            </linearGradient>
        </defs>
        <path d="M5 11h9.2l9.8 9.8L33.8 11H43v9.2L24 43 5 20.2z" fill="url(#{{ $gradId }})"/>
        <path d="M5 11h9.2l9.8 9.8-2.1 2.1L5 15.1z" fill="#fff" fill-opacity=".16"/>
    </svg>
    @if ($wordmark)
        <span class="font-semibold uppercase tracking-[0.2em] text-[0.95em] leading-none">Ubiqa</span>
    @endif
</span>

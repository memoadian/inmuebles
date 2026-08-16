@props(['status'])

@php
    $map = [
        'draft'     => ['Borrador',  'bg-slate-100 text-slate-700'],
        'published' => ['Publicada', 'bg-emerald-100 text-emerald-700'],
        'reserved'  => ['Apartada',  'bg-amber-100 text-amber-700'],
        'sold'      => ['Vendida',   'bg-blue-100 text-blue-700'],
        'rented'    => ['Rentada',   'bg-indigo-100 text-indigo-700'],
        'inactive'  => ['Inactiva',  'bg-slate-100 text-slate-500'],
    ];
    [$label, $classes] = $map[$status] ?? [$status, 'bg-slate-100 text-slate-700'];
@endphp

<span {{ $attributes->merge(['class' => "shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $label }}
</span>

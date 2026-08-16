@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    @php
        $cards = [
            ['label' => 'Total',       'value' => $stats['total'],     'icon' => 'houses',        'color' => 'slate'],
            ['label' => 'Publicadas',  'value' => $stats['published'], 'icon' => 'check-circle',  'color' => 'emerald'],
            ['label' => 'Borradores',  'value' => $stats['draft'],     'icon' => 'pencil-square','color' => 'amber'],
            ['label' => 'Cerradas',    'value' => $stats['sold'],      'icon' => 'flag',          'color' => 'blue'],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ($cards as $card)
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">{{ $card['label'] }}</p>
                    <i class="bi bi-{{ $card['icon'] }} text-{{ $card['color'] }}-500"></i>
                </div>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
            <h2 class="font-medium text-slate-800">Propiedades recientes</h2>
            @can('properties.create')
                <a href="{{ route('properties.create') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-800">
                    <i class="bi bi-plus-lg"></i>
                    <span>Nueva</span>
                </a>
            @endcan
        </div>

        @if ($recent->isEmpty())
            <div class="px-4 py-12 text-center">
                <i class="bi bi-house-add text-4xl text-slate-300"></i>
                <p class="mt-2 text-sm text-slate-500">Aún no hay propiedades registradas.</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($recent as $property)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <div class="h-12 w-16 shrink-0 rounded-lg bg-slate-100 overflow-hidden">
                            @if ($property->cover)
                                <img src="{{ $property->cover->thumb_url }}" alt=""
                                     class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full flex items-center justify-center">
                                    <i class="bi bi-image text-slate-300"></i>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('properties.edit', $property) }}"
                               class="block truncate text-sm font-medium text-slate-800 hover:underline">
                                {{ $property->title }}
                            </a>
                            <p class="truncate text-xs text-slate-500">
                                {{ $property->type?->name }}
                                @if ($property->city) &middot; {{ $property->city->name }} @endif
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-sm font-medium text-slate-900">
                                ${{ number_format($property->price, 0) }} {{ $property->currency }}
                            </p>
                            <p class="text-xs text-slate-500">{{ $property->status }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection

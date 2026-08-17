<!DOCTYPE html>
<html lang="es" class="h-full overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') - {{ config('app.name', 'Inmuebles') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- El panel es un shell fijo: sólo el contenedor interno hace scroll, nunca el
     documento. Sin overflow-hidden aquí, html/body crecen por su cuenta y dejan
     un espacio scrolleable vacío debajo del layout. --}}
<body class="bg-slate-100 h-full overflow-hidden">
    <div class="flex h-dvh overflow-hidden" id="app">
        <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>

        <aside id="sidebar"
               class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200
                      transform -translate-x-full md:translate-x-0 transition-transform duration-200">
            @include('layouts.sidebar')
        </aside>

        <main class="flex-1 flex flex-col min-w-0">
            <header class="relative z-[45] h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 md:px-6 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <button id="menuBtn" class="md:hidden shrink-0 -ml-2 p-2 text-slate-600 hover:bg-slate-100 rounded-lg">
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    <h1 class="text-base font-semibold text-slate-800 truncate">@yield('title', 'Panel')</h1>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('public.properties.index') }}" target="_blank"
                       class="hidden sm:inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>Ver sitio</span>
                    </a>

                    <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
                        <div class="hidden sm:block text-right leading-tight">
                            <p class="text-sm font-medium text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->getRoleNames()->implode(', ') }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Cerrar sesión"
                                    class="p-2 text-slate-600 hover:bg-slate-100 hover:text-red-600 rounded-lg">
                                <i class="bi bi-box-arrow-right text-lg"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 md:p-6">
                <x-alerts />
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inmuebles') - {{ config('app.name', 'Inmuebles') }}</title>
    <meta name="description" content="@yield('meta_description', 'Encuentra casas, departamentos, terrenos y locales en venta o renta en México, publicados directamente por sus dueños y agentes.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', 'Inmuebles') - {{ config('app.name', 'Inmuebles') }}">
    <meta property="og:description" content="@yield('meta_description', 'Encuentra casas, departamentos, terrenos y locales en venta o renta en México.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fraunces:500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css'])
    @stack('json_ld')
</head>
<body class="bg-stone-50 min-h-screen flex flex-col text-stone-800 antialiased">
    <header class="bg-stone-50/90 backdrop-blur border-b border-stone-200 sticky top-0 z-40">
        <div class="mx-auto max-w-7xl px-4 h-18 flex items-center justify-between py-3">
            <a href="{{ route('public.properties.index') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-white shadow-sm">
                    <i class="bi bi-houses-fill text-lg"></i>
                </span>
                <span class="font-serif text-xl font-semibold tracking-tight text-brand-900">
                    {{ config('app.name', 'Inmuebles') }}
                </span>
            </a>

            <nav class="flex items-center gap-2 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-brand-700 px-4 py-2 font-medium text-white shadow-sm hover:bg-brand-800 transition-colors">
                        <i class="bi bi-speedometer2"></i>
                        <span>Mi panel</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-full px-3 py-2 font-medium text-stone-600 hover:text-brand-800 transition-colors">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('register') }}"
                       class="rounded-full bg-brand-700 px-4 py-2 font-medium text-white shadow-sm hover:bg-brand-800 transition-colors">
                        Crear cuenta
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <footer class="bg-brand-950 text-stone-300 mt-16">
        <div class="mx-auto max-w-7xl px-4 py-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <a href="{{ route('public.properties.index') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-white">
                        <i class="bi bi-houses-fill text-lg"></i>
                    </span>
                    <span class="font-serif text-xl font-semibold text-white">
                        {{ config('app.name', 'Inmuebles') }}
                    </span>
                </a>
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-stone-400">
                    Encuentra casas, departamentos y terrenos en venta o renta, publicados
                    directamente por sus dueños y agentes.
                </p>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-stone-400">Explorar</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('public.properties.index') }}" class="hover:text-white transition-colors">Catálogo de propiedades</a></li>
                    <li><a href="{{ route('public.properties.index', ['operation' => 'sale']) }}" class="hover:text-white transition-colors">Propiedades en venta</a></li>
                    <li><a href="{{ route('public.properties.index', ['operation' => 'rent']) }}" class="hover:text-white transition-colors">Propiedades en renta</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-stone-400">Cuenta</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="hover:text-white transition-colors">Mi panel</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Iniciar sesión</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">Crear cuenta</a></li>
                    @endauth
                </ul>
            </div>
        </div>

        <div class="border-t border-brand-900/80">
            <div class="mx-auto max-w-7xl px-4 py-5 text-xs text-stone-500">
                &copy; {{ date('Y') }} {{ config('app.name', 'Inmuebles') }}. Todos los derechos reservados.
            </div>
        </div>
    </footer>
</body>
</html>

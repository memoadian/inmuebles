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
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#0b1440">
    <meta name="mapbox-token" content="{{ config('services.mapbox.token') }}">
    <meta name="mapbox-style" content="{{ config('services.mapbox.style') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fraunces:500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css'])
    @stack('json_ld')
</head>
<body class="bg-slate-50 min-h-screen flex flex-col text-slate-800 antialiased">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-950/85 text-white backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 h-18 flex items-center justify-between gap-6 py-3">
            <div class="flex items-center gap-7">
                <a href="{{ route('home') }}" aria-label="{{ config('app.name', 'Ubiqa') }} — inicio">
                    <img src="{{ asset('images/logo-ubiqa-horizontal.png') }}" alt="{{ config('app.name', 'Ubiqa') }}"
                         width="520" height="173" class="h-9 w-auto">
                </a>
                <nav class="hidden md:flex items-center gap-5 text-sm text-slate-300">
                    <a href="{{ route('public.properties.index', ['operation' => 'sale']) }}"
                       class="hover:text-white transition-colors">Comprar</a>
                    <a href="{{ route('public.properties.index', ['operation' => 'rent']) }}"
                       class="hover:text-white transition-colors">Rentar</a>
                    <a href="{{ route('register') }}" class="hover:text-white transition-colors">Publica tu inmueble</a>
                </nav>
            </div>

            <nav class="flex items-center gap-1.5 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 font-medium text-brand-950 shadow-sm hover:bg-brand-100 transition-colors">
                        <i class="bi bi-speedometer2"></i>
                        <span>Mi panel</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-full px-3 py-2 font-medium text-slate-300 hover:text-white transition-colors">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('register') }}"
                       class="rounded-full bg-white px-4 py-2 font-medium text-brand-950 shadow-sm hover:bg-brand-100 transition-colors">
                        Crear cuenta
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <footer class="bg-brand-950 text-slate-300 mt-16">
        <div class="mx-auto max-w-7xl px-4 py-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <a href="{{ route('public.properties.index') }}" class="inline-block" aria-label="{{ config('app.name', 'Ubiqa') }} — inicio">
                    <img src="{{ asset('images/logo-ubiqa.png') }}" alt="{{ config('app.name', 'Ubiqa') }}"
                         width="180" height="145" class="h-24 w-auto -ml-3">
                </a>
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-slate-400">
                    Encuentra casas, departamentos y terrenos en venta o renta, publicados
                    directamente por sus dueños y agentes.
                </p>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Explorar</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('public.properties.index') }}" class="hover:text-white transition-colors">Catálogo de propiedades</a></li>
                    <li><a href="{{ route('public.properties.index', ['operation' => 'sale']) }}" class="hover:text-white transition-colors">Propiedades en venta</a></li>
                    <li><a href="{{ route('public.properties.index', ['operation' => 'rent']) }}" class="hover:text-white transition-colors">Propiedades en renta</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Cuenta</p>
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
            <div class="mx-auto max-w-7xl px-4 py-5 text-xs text-slate-500">
                &copy; {{ date('Y') }} {{ config('app.name', 'Inmuebles') }}. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

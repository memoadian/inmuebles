<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - {{ config('app.name', 'Inmuebles') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <i class="bi bi-houses-fill text-4xl text-slate-900"></i>
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ config('app.name', 'Inmuebles') }}</h1>
                <p class="text-sm text-slate-500">Ingresa a tu cuenta para continuar</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                      focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                        <input id="password" name="password" type="password" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                      focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1"
                               class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        <span>Mantener sesión iniciada</span>
                    </label>

                    <button type="submit"
                            class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white
                                   hover:bg-slate-800 transition-colors">
                        Iniciar sesión
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-slate-600">
                    ¿No tienes cuenta?
                    <a href="{{ route('register') }}" class="font-medium text-slate-900 hover:underline">Regístrate</a>
                </p>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="{{ route('public.properties.index') }}" class="hover:underline">
                    <i class="bi bi-arrow-left"></i> Ver el catálogo público
                </a>
            </p>
        </div>
    </div>
</body>
</html>

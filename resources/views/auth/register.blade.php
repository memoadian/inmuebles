<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta - {{ config('app.name', 'Inmuebles') }}</title>
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
                <h1 class="mt-2 text-2xl font-semibold text-slate-900">Crear cuenta</h1>
                <p class="text-sm text-slate-500">Regístrate para guardar y consultar inmuebles</p>
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

                <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nombre completo</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                      focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                      focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">
                            Teléfono <span class="text-slate-400 font-normal">(opcional)</span>
                        </label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                      focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                        <input id="password" name="password" type="password" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                      focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
                        <p class="mt-1 text-xs text-slate-500">Mínimo 8 caracteres.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">
                            Confirmar contraseña
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
                                      focus:border-slate-900 focus:ring-1 focus:ring-slate-900 outline-none">
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white
                                   hover:bg-slate-800 transition-colors">
                        Crear cuenta
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-slate-600">
                    ¿Ya tienes cuenta?
                    <a href="{{ route('login') }}" class="font-medium text-slate-900 hover:underline">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

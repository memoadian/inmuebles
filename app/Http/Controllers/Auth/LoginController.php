<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $this->checkTooManyFailedAttempts($request);

        if ($this->attemptLogin($request)) {
            RateLimiter::clear($this->throttleKey($request));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($this->throttleKey($request), 60);

        throw ValidationException::withMessages([
            'email' => [__('Las credenciales proporcionadas no son correctas.')],
        ]);
    }

    protected function attemptLogin(Request $request): bool
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return false;
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('Su cuenta ha sido desactivada. Contacte al administrador.')],
            ]);
        }

        if (! Hash::check($request->input('password'), $user->password)) {
            return false;
        }

        Auth::login($user, $request->boolean('remember'));

        return true;
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function checkTooManyFailedAttempts(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => [__('Demasiados intentos de inicio de sesión. Intente nuevamente en :seconds segundos.', [
                'seconds' => RateLimiter::availableIn($this->throttleKey($request)),
            ])],
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}

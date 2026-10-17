<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.login', ['intended' => $request->query('redirect', $request->input('redirect'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:160'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $ok = Auth::attempt([
            $field => $credentials['login'],
            'password' => $credentials['password'],
        ], (bool) $request->boolean('remember'));

        if (! $ok) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'login' => 'Неверные данные для входа.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        $redirect = $request->input('redirect');
        if (is_string($redirect) && $redirect !== '' && preg_match('#^/(?!/)#', $redirect)) {
            return redirect($redirect)->with('success', 'Вы вошли в личный кабинет.');
        }

        return redirect()->intended(route('account.dashboard'))
            ->with('success', 'Вы вошли в личный кабинет.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Вы вышли из аккаунта.');
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'login' => 'Слишком много попыток входа. Повторите через '.$seconds.' с.',
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('login')).'|'.$request->ip());
    }
}

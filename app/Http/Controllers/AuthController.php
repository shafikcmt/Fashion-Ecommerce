<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function form(string $mode = 'login'): View
    {
        return view('auth.form', compact('mode'));
    }

    public function register(Request $r): RedirectResponse
    {
        $r->merge(['email' => strtolower((string) $r->input('email'))]);
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()]]);
        $data['email'] = strtolower($data['email']);
        $u = User::create($data);
        event(new Registered($u));
        Auth::login($u);
        $r->session()->regenerate();
        app(CartService::class)->merge($u->id);

        return redirect()->route('verification.notice');
    }

    public function login(Request $r): RedirectResponse
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $data['email'] = strtolower($data['email']);
        if (! Auth::attempt($data, $r->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        $r->session()->regenerate();
        app(CartService::class)->merge(Auth::id());

        return redirect()->intended(route('account'));
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function forgot(Request $r): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:255']);
        Password::sendResetLink(['email' => strtolower($r->email)]);

        return back()->with('status', 'If that account exists, a password reset link has been sent.');
    }

    public function resetForm(Request $r, string $token): View
    {
        return view('auth.form', ['mode' => 'reset', 'token' => $token, 'email' => $r->query('email')]);
    }

    public function reset(Request $r): RedirectResponse
    {
        $data = $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()]]);
        $status = Password::reset($data, function (User $u, string $password) {
            $u->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($u));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', 'Password reset. Sign in with your new password.');
    }

    public function verify(EmailVerificationRequest $r): RedirectResponse
    {
        $r->fulfill();

        return redirect()->route('account')->with('status', 'Email verified.');
    }

    public function resend(Request $r): RedirectResponse
    {
        if (! $r->user()->hasVerifiedEmail()) {
            $r->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Verification link sent.');
    }
}

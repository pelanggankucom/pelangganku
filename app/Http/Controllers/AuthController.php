<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function show(): mixed
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isOwner() ? 'owner.dashboard' : 'kasir');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun nonaktif. Hubungi owner.',
            ]);
        }

        $request->session()->regenerate();

        // Owner selalu mendarat di Beranda (toko bisa diganti dari sana).
        if (Auth::user()->isOwner()) {
            return redirect()->route('owner.dashboard');
        }

        return redirect()->intended(route('kasir'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

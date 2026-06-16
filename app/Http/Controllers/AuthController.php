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

        // Owner perlu pilih merchant jika punya multiple
        if (Auth::user()->isOwner()) {
            try {
                $merchants = Auth::user()->merchants()->get();
                if ($merchants->count() === 1) {
                    // Auto-select jika cuma 1 merchant
                    session(['selected_merchant_id' => $merchants->first()->id]);
                    return redirect()->route('owner.dashboard');
                } elseif ($merchants->count() > 1) {
                    // Redirect ke merchant selector
                    return redirect()->route('merchant.select');
                }
            } catch (\Exception $e) {
                // Fallback: gunakan merchant_id column
                if (Auth::user()->merchant_id) {
                    session(['selected_merchant_id' => Auth::user()->merchant_id]);
                    return redirect()->route('owner.dashboard');
                }
            }
        }

        $home = Auth::user()->isOwner() ? route('owner.dashboard') : route('kasir');
        return redirect()->intended($home);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

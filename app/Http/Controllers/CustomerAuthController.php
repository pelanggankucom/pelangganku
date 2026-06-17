<?php

namespace App\Http\Controllers;

use App\Models\CustomerAccount;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function showLogin(): mixed
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('member.dashboard');
        }

        return view('member.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $canonical = PhoneNumber::normalize($request->input('phone'));
        $account = $canonical ? CustomerAccount::where('phone_canonical', $canonical)->first() : null;

        if (! $account || ! Hash::check($request->input('password'), $account->password)) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP atau password salah.',
            ]);
        }

        Auth::guard('customer')->login($account, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('member.dashboard');
    }

    public function showRegister(): mixed
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('member.dashboard');
        }

        return view('member.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $canonical = PhoneNumber::normalize($data['phone']);
        if ($canonical === null) {
            return back()->withInput()->withErrors(['phone' => 'Nomor HP tidak valid.']);
        }

        if (CustomerAccount::where('phone_canonical', $canonical)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'Nomor ini sudah terdaftar. Silakan masuk.']);
        }

        // TODO: verifikasi OTP (dilewati untuk sekarang).

        $account = CustomerAccount::create([
            'name' => $data['name'],
            'phone_canonical' => $canonical,
            'password' => $data['password'],
        ]);

        Auth::guard('customer')->login($account);
        $request->session()->regenerate();

        return redirect()->route('member.dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('member.login');
    }
}

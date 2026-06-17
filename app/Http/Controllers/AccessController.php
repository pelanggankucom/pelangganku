<?php

namespace App\Http\Controllers;

use App\Models\CustomerAccount;
use App\Models\Merchant;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Autentikasi terpadu (owner / kasir / pelanggan) berbasis nomor HP.
 *
 * Catatan keamanan: verifikasi OTP via WhatsApp BELUM diterapkan. Sebelum
 * rilis publik, daftar & lupa-password WAJIB digerbangi OTP — saat ini siapa
 * pun yang tahu nomor bisa reset password. (TODO: integrasi OTP WA.)
 */
class AccessController extends Controller
{
    public function showLogin(): mixed
    {
        return $this->redirectIfLoggedIn() ?? view('access.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $canonical = PhoneNumber::normalize($request->input('phone'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // 1) Staf (owner / kasir)
        $user = $canonical ? User::where('phone', $canonical)->first() : null;
        if ($user && Hash::check($password, $user->password)) {
            if (! $user->is_active) {
                throw ValidationException::withMessages(['phone' => 'Akun nonaktif. Hubungi owner.']);
            }
            Auth::guard('web')->login($user, $remember);
            $request->session()->regenerate();

            return redirect()->intended($user->isOwner() ? route('owner.dashboard') : route('kasir'));
        }

        // 2) Pelanggan
        $acct = $canonical ? CustomerAccount::where('phone_canonical', $canonical)->first() : null;
        if ($acct && Hash::check($password, $acct->password)) {
            Auth::guard('customer')->login($acct, $remember);
            $request->session()->regenerate();

            return redirect()->route('member.dashboard');
        }

        throw ValidationException::withMessages(['phone' => 'Nomor HP atau password salah.']);
    }

    public function showRegister(): mixed
    {
        return $this->redirectIfLoggedIn() ?? view('access.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'peran' => ['required', 'in:owner,pelanggan'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'store' => ['required_if:peran,owner', 'nullable', 'string', 'max:120'],
        ]);

        $canonical = PhoneNumber::normalize($data['phone']);
        if ($canonical === null) {
            return back()->withInput()->withErrors(['phone' => 'Nomor HP tidak valid.']);
        }

        // TODO: verifikasi OTP WhatsApp sebelum membuat akun.

        if ($data['peran'] === 'owner') {
            if (User::where('phone', $canonical)->exists()) {
                return back()->withInput()->withErrors(['phone' => 'Nomor ini sudah terdaftar sebagai staf. Silakan masuk.']);
            }

            $merchant = Merchant::create(['name' => $data['store'], 'is_active' => true]);

            $user = User::create([
                'name' => $data['name'],
                'phone' => $canonical,
                'email' => $canonical . '@owner.pelangganku.local',
                'password' => $data['password'],
                'role' => User::ROLE_OWNER,
                'is_active' => true,
            ]);

            $merchant->forceFill(['owner_user_id' => $user->id])->save();
            $user->merchants()->attach($merchant->id, ['role' => 'owner']);

            // Outlet & program default agar owner langsung bisa pakai.
            $merchant->branches()->create(['name' => 'Outlet Utama', 'is_active' => true]);
            $merchant->loyaltyPrograms()->create([
                'name' => 'Program Stempel',
                'card_size' => 10,
                'stamps_per_reward' => 10,
                'earn_rule' => 'per_visit',
                'carry_over' => true,
                'is_active' => true,
            ]);

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return redirect()->route('owner.dashboard')->with('success', 'Toko berhasil dibuat. Selamat datang!');
        }

        // Pelanggan
        if (CustomerAccount::where('phone_canonical', $canonical)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'Nomor ini sudah terdaftar. Silakan masuk.']);
        }

        $acct = CustomerAccount::create([
            'name' => $data['name'],
            'phone_canonical' => $canonical,
            'password' => $data['password'],
        ]);

        Auth::guard('customer')->login($acct);
        $request->session()->regenerate();

        return redirect()->route('member.dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }

    public function showForgot(): mixed
    {
        return $this->redirectIfLoggedIn() ?? view('access.forgot');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $canonical = PhoneNumber::normalize($data['phone']);
        if ($canonical === null) {
            return back()->withInput()->withErrors(['phone' => 'Nomor HP tidak valid.']);
        }

        // TODO: verifikasi OTP WhatsApp sebelum mengizinkan reset.

        $user = User::where('phone', $canonical)->first();
        if ($user) {
            if ($user->isCashier()) {
                return back()->withErrors(['phone' => 'Akun kasir tidak bisa reset password sendiri. Hubungi owner.']);
            }
            $user->password = $data['password'];
            $user->save();

            return redirect()->route('login')->with('success', 'Password diperbarui. Silakan masuk.');
        }

        $acct = CustomerAccount::where('phone_canonical', $canonical)->first();
        if ($acct) {
            $acct->password = $data['password'];
            $acct->save();

            return redirect()->route('login')->with('success', 'Password diperbarui. Silakan masuk.');
        }

        return back()->withErrors(['phone' => 'Nomor HP tidak terdaftar.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectIfLoggedIn(): ?RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route(Auth::guard('web')->user()->isOwner() ? 'owner.dashboard' : 'kasir');
        }
        if (Auth::guard('customer')->check()) {
            return redirect()->route('member.dashboard');
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CustomerAccount;
use App\Models\Merchant;
use App\Models\User;
use App\Services\OtpService;
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

            if ($user->isSuperAdmin()) {
                return redirect()->intended(route('superadmin.dashboard'));
            }
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

        // Cek duplikasi nomor
        if ($data['peran'] === 'owner' && User::where('phone', $canonical)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'Nomor ini sudah terdaftar sebagai staf. Silakan masuk.']);
        }
        if ($data['peran'] === 'pelanggan' && CustomerAccount::where('phone_canonical', $canonical)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'Nomor ini sudah terdaftar. Silakan masuk.']);
        }

        // Request OTP
        $otpService = new OtpService();
        $otpResult = $otpService->sendOtp($canonical);

        // Kalau Fonnte gagal (device disconnect, dll):
        // - Owner: tetap blokir, OTP wajib
        // - Pelanggan: langsung buat akun tanpa OTP
        if (!$otpResult['success']) {
            if ($data['peran'] === 'owner') {
                return back()->withInput()->withErrors(['phone' => $otpResult['message']]);
            }

            // Bypass OTP untuk pelanggan
            $acct = CustomerAccount::create([
                'name'           => $data['name'],
                'phone_canonical' => $canonical,
                'password'       => $data['password'],
            ]);
            Auth::guard('customer')->login($acct);
            $request->session()->regenerate();

            return redirect()->route('member.dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
        }

        // Store data di session untuk verification step
        $request->session()->put([
            'register_pending' => true,
            'register_peran' => $data['peran'],
            'register_name' => $data['name'],
            'register_phone' => $canonical,
            'register_password' => $data['password'],
            'register_store' => $data['store'] ?? null,
        ]);

        return redirect()->route('register.otp.show')->with('message', 'Kode OTP telah dikirim ke WhatsApp Anda.');
    }

    public function showRegisterOtp(): mixed
    {
        if (!session('register_pending')) {
            return redirect()->route('register');
        }
        return view('access.register-otp-verify');
    }

    public function registerOtpVerify(Request $request): RedirectResponse
    {
        if (!session('register_pending')) {
            return redirect()->route('register');
        }

        $request->validate(['otp' => ['required', 'string', 'size:6']]);

        $otpService = new OtpService();
        $result = $otpService->verifyOtp(session('register_phone'), $request->otp);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        // OTP valid, buat akun
        $peran = session('register_peran');
        $canonical = session('register_phone');

        if ($peran === 'owner') {
            $merchant = Merchant::create(['name' => session('register_store'), 'is_active' => true]);

            $user = User::create([
                'name' => session('register_name'),
                'phone' => $canonical,
                'email' => $canonical . '@owner.pelangganku.local',
                'password' => session('register_password'),
                'role' => User::ROLE_OWNER,
                'is_active' => true,
            ]);

            $merchant->forceFill(['owner_user_id' => $user->id])->save();
            $user->merchants()->attach($merchant->id, ['role' => 'owner']);

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
            $request->session()->forget(['register_pending', 'register_peran', 'register_name', 'register_phone', 'register_password', 'register_store']);
            $request->session()->regenerate();

            return redirect()->route('owner.dashboard')->with('success', 'Toko berhasil dibuat. Selamat datang!');
        }

        // Pelanggan
        $acct = CustomerAccount::create([
            'name' => session('register_name'),
            'phone_canonical' => $canonical,
            'password' => session('register_password'),
        ]);

        Auth::guard('customer')->login($acct);
        $request->session()->forget(['register_pending', 'register_peran', 'register_name', 'register_phone', 'register_password', 'register_store']);
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

        // Check apakah nomor terdaftar
        $user = User::where('phone', $canonical)->first();
        $acct = CustomerAccount::where('phone_canonical', $canonical)->first();

        if (!$user && !$acct) {
            return back()->withErrors(['phone' => 'Nomor HP tidak terdaftar.']);
        }

        if ($user && $user->isCashier()) {
            return back()->withErrors(['phone' => 'Akun kasir tidak bisa reset password sendiri. Hubungi owner.']);
        }

        // Request OTP
        $otpService = new OtpService();
        $result = $otpService->sendOtp($canonical);

        // Kalau Fonnte gagal:
        // - Pelanggan: langsung terapkan password baru tanpa OTP
        // - Owner: tetap blokir, OTP wajib
        if (!$result['success']) {
            if ($acct) {
                $acct->password = $data['password'];
                $acct->save();
                return redirect()->route('login')->with('success', 'Password diperbarui. Silakan masuk.');
            }
            return back()->withInput()->withErrors(['phone' => $result['message']]);
        }

        // Store data di session untuk verification step
        $request->session()->put([
            'forgot_pending' => true,
            'forgot_phone' => $canonical,
            'forgot_password' => $data['password'],
        ]);

        return redirect()->route('forgot.otp.show')->with('message', 'Kode OTP telah dikirim ke WhatsApp Anda.');
    }

    public function showForgotOtp(): mixed
    {
        if (!session('forgot_pending')) {
            return redirect()->route('password.request');
        }
        return view('access.forgot-otp-verify');
    }

    public function forgotOtpVerify(Request $request): RedirectResponse
    {
        if (!session('forgot_pending')) {
            return redirect()->route('password.request');
        }

        $request->validate(['otp' => ['required', 'string', 'size:6']]);

        $otpService = new OtpService();
        $result = $otpService->verifyOtp(session('forgot_phone'), $request->otp);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        // OTP valid, update password
        $canonical = session('forgot_phone');
        $newPassword = session('forgot_password');

        $user = User::where('phone', $canonical)->first();
        if ($user) {
            $user->password = $newPassword;
            $user->save();
        } else {
            $acct = CustomerAccount::where('phone_canonical', $canonical)->first();
            if ($acct) {
                $acct->password = $newPassword;
                $acct->save();
            }
        }

        $request->session()->forget(['forgot_pending', 'forgot_phone', 'forgot_password']);

        return redirect()->route('login')->with('success', 'Password diperbarui. Silakan masuk.');
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
            $user = Auth::guard('web')->user();
            if ($user->isSuperAdmin()) return redirect()->route('superadmin.dashboard');
            return redirect()->route($user->isOwner() ? 'owner.dashboard' : 'kasir');
        }
        if (Auth::guard('customer')->check()) {
            return redirect()->route('member.dashboard');
        }

        return null;
    }
}

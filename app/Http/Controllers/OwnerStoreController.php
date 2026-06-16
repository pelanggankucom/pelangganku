<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OwnerStoreController extends Controller
{
    public function edit(): View
    {
        return view('owner.store', ['merchant' => auth()->user()->merchant]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'facebook' => ['nullable', 'string', 'max:120'],
            'tiktok' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $merchant = auth()->user()->merchant;

        if ($request->hasFile('logo')) {
            if ($merchant->logo_path) {
                Storage::disk('public')->delete($merchant->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('photo')) {
            if ($merchant->photo_path) {
                Storage::disk('public')->delete($merchant->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('photos', 'public');
        }

        unset($data['logo'], $data['photo']);
        $merchant->update($data);

        return back()->with('success', 'Profil toko tersimpan.');
    }
}

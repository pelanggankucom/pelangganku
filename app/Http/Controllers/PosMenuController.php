<?php

namespace App\Http\Controllers;

use App\Models\PosMenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosMenuController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();

        if (!$merchant->hasPosAccess()) {
            return redirect()->route('owner.pos')
                ->with('error', 'Aktifkan POS terlebih dahulu untuk mengelola menu.');
        }

        $items = PosMenuItem::where('merchant_id', $merchant->id)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = $items->pluck('category')->filter()->unique()->sort()->values();

        return view('owner.pos-menu', compact('merchant', 'items', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(!$merchant->hasPosAccess(), 403);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'price'    => ['required', 'integer', 'min:0', 'max:99999999'],
        ]);

        PosMenuItem::create([
            'merchant_id' => $merchant->id,
            'name'        => $data['name'],
            'category'    => $data['category'] ?: null,
            'price'       => $data['price'],
            'is_active'   => true,
        ]);

        return back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, PosMenuItem $item): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if($item->merchant_id !== $merchant->id, 403);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'category'  => ['nullable', 'string', 'max:60'],
            'price'     => ['required', 'integer', 'min:0', 'max:99999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $item->update([
            'name'      => $data['name'],
            'category'  => $data['category'] ?: null,
            'price'     => $data['price'],
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $item->is_active,
        ]);

        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(PosMenuItem $item): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if($item->merchant_id !== $merchant->id, 403);

        $item->delete();

        return back()->with('success', 'Menu berhasil dihapus.');
    }
}

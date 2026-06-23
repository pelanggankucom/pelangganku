<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterSettingsController extends Controller
{
    public function edit(): View
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        return view('owner.printer', [
            'merchant' => $merchant,
            'settings' => $merchant->printerSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        $validated = $request->validate([
            'footer_text'   => ['nullable', 'string', 'max:200'],
            'show_address'  => ['nullable', 'boolean'],
            'show_whatsapp' => ['nullable', 'boolean'],
            'auto_print'    => ['nullable', 'boolean'],
        ]);

        $merchant->update([
            'printer_settings' => [
                'footer_text'   => $validated['footer_text'] ?? '',
                'show_address'  => (bool) ($validated['show_address'] ?? false),
                'show_whatsapp' => (bool) ($validated['show_whatsapp'] ?? false),
                'auto_print'    => (bool) ($validated['auto_print'] ?? false),
            ],
        ]);

        return redirect()->route('owner.printer')->with('success', 'Pengaturan printer disimpan.');
    }
}

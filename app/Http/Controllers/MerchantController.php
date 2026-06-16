<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MerchantController extends Controller
{
    public function select(): View
    {
        $merchants = auth()->user()->merchants()->get();

        return view('merchant.select', compact('merchants'));
    }

    public function switch(Request $request): RedirectResponse
    {
        $merchantId = $request->validate(['merchant_id' => 'required|exists:merchants,id']);

        // Verify user owns this merchant
        $merchant = auth()->user()->merchants()->find($merchantId['merchant_id']);
        if (!$merchant) {
            abort(403);
        }

        session(['selected_merchant_id' => $merchant->id]);

        return redirect()->route('owner.dashboard');
    }
}

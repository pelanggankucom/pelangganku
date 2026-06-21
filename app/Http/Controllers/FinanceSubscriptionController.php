<?php

namespace App\Http\Controllers;

use App\Models\FinanceSubscription;
use App\Services\DokuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceSubscriptionController extends Controller
{
    public function show(): View
    {
        $merchant = auth()->user()->currentMerchant();
        $sub      = FinanceSubscription::where('merchant_id', $merchant->id)->first();

        return view('owner.laporan-sub', compact('merchant', 'sub'));
    }

    public function subscribe(): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        $user     = auth()->user();

        $invoiceNumber = 'FIN-' . $merchant->id . '-' . now()->format('YmdHis');

        try {
            $doku   = new DokuService();
            $result = $doku->createPayment([
                'amount'              => 25000,
                'invoice_number'      => $invoiceNumber,
                'customer_name'       => $user->name,
                'customer_email'      => $user->email ?? 'owner@pelangganku.local',
                'callback_url'        => route('owner.laporan.return', ['inv' => $invoiceNumber]),
                'callback_url_cancel' => route('owner.laporan.sub'),
                'notify_url'          => route('finance.webhook'),
            ]);

            FinanceSubscription::updateOrCreate(
                ['merchant_id' => $merchant->id],
                [
                    'status'              => 'pending',
                    'doku_invoice_number' => $invoiceNumber,
                    'doku_payment_url'    => $result['url'],
                    'amount'              => 25000,
                ]
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Pembayaran gagal: ' . $e->getMessage());
        }

        return redirect($result['url']);
    }

    public function return(Request $request): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        $sub      = FinanceSubscription::where('merchant_id', $merchant->id)->first();

        if ($sub && $sub->isActive()) {
            return redirect()->route('owner.laporan.sub')
                ->with('success', 'Laporan Keuangan berhasil diaktifkan! 🎉');
        }

        return redirect()->route('owner.laporan.sub')
            ->with('info', 'Pembayaran sedang diverifikasi. Laporan akan aktif dalam beberapa menit.');
    }

    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $invoiceNumber = $data['order']['invoice_number']
            ?? $data['original_partner_reference_no']
            ?? null;

        if (! $invoiceNumber) return response('OK', 200);

        $sub = FinanceSubscription::where('doku_invoice_number', $invoiceNumber)->first();
        if (! $sub) return response('OK', 200);

        $status = $data['transaction']['status']
            ?? $data['additional_info']['transaction_status']
            ?? '';

        if (in_array(strtoupper($status), ['SUCCESS', 'PAID', 'SETTLEMENT', 'CAPTURE'])) {
            $from = $sub->isActive() ? $sub->expires_at : now();
            $sub->update([
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => $from->copy()->addMonth(),
            ]);
        }

        return response('OK', 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PosSubscription;
use App\Services\DokuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosSubscriptionController extends Controller
{
    public function show(): View
    {
        $merchant = auth()->user()->currentMerchant();
        $sub      = PosSubscription::where('merchant_id', $merchant->id)->first();

        return view('owner.pos', compact('merchant', 'sub'));
    }

    public function subscribe(): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        $user     = auth()->user();

        $invoiceNumber = 'POS-' . $merchant->id . '-' . now()->format('YmdHis');

        try {
            $doku   = new DokuService();
            $result = $doku->createPayment([
                'amount'               => 25000,
                'invoice_number'       => $invoiceNumber,
                'customer_name'        => $user->name,
                'customer_email'       => $user->email ?? 'owner@pelangganku.local',
                'callback_url'         => route('owner.pos.return', ['inv' => $invoiceNumber]),
                'callback_url_cancel'  => route('owner.pos'),
                'notify_url'           => route('pos.webhook'),
            ]);

            PosSubscription::updateOrCreate(
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

    public function activateTrial(): RedirectResponse
    {
        $merchant = auth()->user()->currentMerchant();
        abort_if(! $merchant, 403);

        if ($merchant->pos_trial_used_at) {
            return redirect()->route('owner.pos')->with('error', 'Akses gratis sudah pernah digunakan untuk toko ini.');
        }
        if ($merchant->hasPosAccess()) {
            return redirect()->route('owner.pos')->with('info', 'POS sudah aktif.');
        }

        PosSubscription::updateOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addMonths(3),
                'amount'     => 0,
            ]
        );
        $merchant->update(['pos_trial_used_at' => now()]);

        return redirect()->route('owner.pos')->with('success', 'Selamat! Akses POS gratis 3 bulan telah aktif. 🎉');
    }

    public function return(Request $request): RedirectResponse
    {
        // Cek apakah subscription sudah di-update oleh webhook
        $merchant = auth()->user()->currentMerchant();
        $sub      = PosSubscription::where('merchant_id', $merchant->id)->first();

        if ($sub && $sub->isActive()) {
            return redirect()->route('owner.pos')->with('success', 'POS berhasil diaktifkan! Selamat berjualan 🎉');
        }

        return redirect()->route('owner.pos')
            ->with('info', 'Pembayaran sedang diverifikasi. POS akan aktif dalam beberapa menit.');
    }

    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $bodyRaw = $request->getContent();
        $data    = json_decode($bodyRaw, true) ?? [];

        // Cari subscription berdasarkan invoice number dari DOKU notification
        $invoiceNumber = $data['order']['invoice_number']
            ?? $data['original_partner_reference_no']
            ?? null;

        if (!$invoiceNumber) {
            return response('OK', 200);
        }

        $sub = PosSubscription::where('doku_invoice_number', $invoiceNumber)->first();

        if (!$sub) {
            return response('OK', 200);
        }

        // Status SUCCESS dari DOKU
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

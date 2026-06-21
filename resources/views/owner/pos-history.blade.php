@extends('layouts.app')
@section('title', 'Riwayat Transaksi POS')

@section('content')
<style>
    .trx-row { display:flex; align-items:center; gap:10px; padding:13px 16px; border-bottom:1px solid var(--line); }
    .trx-row:last-child { border-bottom:none; }
    .trx-method { font-size:11.5px; color:var(--muted); font-weight:700; text-transform:uppercase; min-width:52px; text-align:right; }
    .trx-amount { font-size:14px; font-weight:800; color:var(--blue); min-width:90px; text-align:right; }
</style>

<div style="margin-bottom:18px;">
    <div style="font-size:18px; font-weight:800; letter-spacing:-.4px;">🧾 Riwayat Transaksi</div>
    <div style="font-size:13px; color:var(--muted);">{{ $merchant->name }}</div>
</div>

@if(session('success'))<div class="flash ok">{{ session('success') }}</div>@endif

@if($orders->isEmpty())
    <div style="text-align:center; padding:48px 20px; color:var(--muted);">
        <div style="font-size:40px; margin-bottom:10px;">🧾</div>
        <p style="font-weight:600;">Belum ada transaksi.</p>
    </div>
@else
    <div class="card" style="padding:0; overflow:hidden; margin-bottom:14px;">
        @foreach($orders as $order)
        <div class="trx-row">
            <div style="flex:1; min-width:0;">
                <div style="font-size:13.5px; font-weight:700;">{{ $order->order_number }}</div>
                <div style="font-size:12px; color:var(--muted);">{{ $order->created_at->format('d M Y · H:i') }}</div>
            </div>
            <div class="trx-method">{{ $order->payment_method }}</div>
            <div class="trx-amount">Rp {{ number_format($order->total, 0, ',', '.') }}</div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
        @if($orders->onFirstPage())
            <span style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--muted); font-size:13px; font-weight:700;">‹</span>
        @else
            <a href="{{ $orders->previousPageUrl() }}" style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--blue); font-size:13px; font-weight:700; text-decoration:none;">‹</a>
        @endif
        <span style="padding:8px 14px; font-size:13px; color:var(--muted); font-weight:600;">
            Hal {{ $orders->currentPage() }} / {{ $orders->lastPage() }}
        </span>
        @if($orders->hasMorePages())
            <a href="{{ $orders->nextPageUrl() }}" style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--blue); font-size:13px; font-weight:700; text-decoration:none;">›</a>
        @else
            <span style="padding:8px 14px; border-radius:10px; border:1.5px solid var(--line); color:var(--muted); font-size:13px; font-weight:700;">›</span>
        @endif
    </div>
    @endif
@endif

<a href="{{ route('owner.dashboard') }}"
   style="display:flex; align-items:center; justify-content:center; gap:8px;
          width:100%; padding:16px; border-radius:16px;
          background:#fff; border:1.5px solid var(--line);
          font-size:15px; font-weight:700; color:var(--navy); text-decoration:none;">
    ← Kembali ke Dashboard
</a>

<div style="height:24px;"></div>
@endsection

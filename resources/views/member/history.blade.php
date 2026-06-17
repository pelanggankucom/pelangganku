@extends('layouts.app')
@section('title', 'Riwayat Penukaran')

@section('content')
<style>
    .htotal { background:var(--grad-gold); color:#3A2A00; border-radius:20px; padding:18px 20px; text-align:center; margin-bottom:16px; box-shadow:0 8px 20px rgba(246,185,49,.28); }
    .htotal .label { font-size:12.5px; font-weight:600; opacity:.85; }
    .htotal .amount { font-size:30px; font-weight:800; letter-spacing:-.8px; margin-top:2px; }
    .hitem { display:flex; gap:13px; align-items:flex-start; padding:14px 0; border-top:1px solid var(--line); }
    .hitem:first-of-type { border-top:none; }
    .hitem .ic { width:44px; height:44px; border-radius:12px; background:#FFF1C9; display:flex; align-items:center; justify-content:center; font-size:22px; flex:none; }
    .hitem .info { flex:1; min-width:0; }
    .hitem .info b { display:block; font-size:15px; }
    .hitem .info .store { font-size:12.5px; color:var(--muted); }
    .hitem .info .date { font-size:11.5px; color:var(--muted); margin-top:2px; }
    .hitem .val { font-weight:800; color:var(--gold-d); font-size:14px; white-space:nowrap; }
</style>

<h1>Riwayat Penukaran</h1>
<p class="sub">Hadiah yang sudah kamu tukarkan & penghematannya.</p>

<div class="htotal">
    <div class="label">Total penghematan</div>
    <div class="amount">Rp {{ number_format($total, 0, ',', '.') }}</div>
</div>

<div class="card">
    @forelse($redemptions as $t)
        @php $r = $t->reward; @endphp
        <div class="hitem">
            <div class="ic">🎁</div>
            <div class="info">
                <b>{{ $r?->name ?? 'Hadiah' }}</b>
                <div class="store">{{ $t->customer?->merchant?->name ?? '-' }}</div>
                <div class="date">{{ $t->created_at->isoFormat('D MMM Y · HH:mm') }}</div>
            </div>
            <div class="val">{{ $r?->value ? 'Rp ' . number_format($r->value, 0, ',', '.') : '-' }}</div>
        </div>
    @empty
        <p class="muted">Belum ada hadiah yang ditukar. Kumpulkan stempel dan tukarkan hadiah pertamamu!</p>
    @endforelse
</div>

<a href="{{ route('member.dashboard') }}" class="btn secondary">← Kembali ke Beranda</a>
@endsection

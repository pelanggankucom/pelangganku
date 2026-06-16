@extends('layouts.app')
@section('title', 'Pilih Toko')

@section('content')
<style>
    .hero { background:linear-gradient(135deg,var(--blue) 0%,var(--blue-l) 100%); color:#fff; border-radius:20px; padding:20px; margin-bottom:20px; }
    .hero .big { font-size:28px; font-weight:800; margin:8px 0; }
    .hero .label { font-size:13px; opacity:.85; }
    .stores { display:grid; gap:14px; }
    .store-card { background:var(--panel); border:2px solid var(--line); border-radius:16px; padding:18px; cursor:pointer; transition:all .2s; }
    .store-card:active { border-color:var(--blue); background:#f8faff; transform:scale(.98); }
    .store-card .name { font-size:18px; font-weight:700; margin-bottom:4px; }
    .store-card .info { font-size:13px; color:var(--muted); }
    .store-card .info span { display:block; }
    .store-card .icon { font-size:32px; margin-bottom:8px; }
</style>

<div class="hero">
    <div class="label">Selamat datang,</div>
    <div class="big">{{ auth()->user()->name }}</div>
    <div style="font-size:13px; margin-top:6px; opacity:.9;">Pilih toko untuk mulai</div>
</div>

<div class="stores">
    @forelse($merchants as $m)
        <form action="{{ route('merchant.switch') }}" method="POST">
            @csrf
            <input type="hidden" name="merchant_id" value="{{ $m->id }}">
            <button type="submit" class="store-card" style="width:100%; text-align:left; border:none; padding:18px; background:var(--panel); border:2px solid var(--line); border-radius:16px; cursor:pointer;">
                <div class="icon">🏪</div>
                <div class="name">{{ $m->name }}</div>
                <div class="info">
                    <span>{{ $m->branches()->where('is_active', true)->count() }} outlet aktif</span>
                    @if($m->address)
                        <span>{{ $m->address }}</span>
                    @endif
                </div>
            </button>
        </form>
    @empty
        <p class="muted">Belum ada toko terdaftar.</p>
    @endforelse
</div>

@endsection

@extends('layouts.app')
@section('title', $customer->name . ' · Profil')

@section('content')
    @php
        $size = $program?->card_size ?? 10;
        $current = $balance?->stamps_current ?? 0;
        $milestones = [];
        foreach ($rewardStatuses as $s) { $milestones[$s['reward']->milestone] = true; }
        $anyClaimable = collect($rewardStatuses)->contains(fn ($s) => $s['claimable']);
    @endphp

    <div class="hero">
        <div class="label">{{ $customer->phone_masked }}</div>
        <div class="big">{{ $customer->name }}</div>
        <div class="label"><span class="coin">★</span> {{ $current }}/{{ $size }} stempel terkumpul</div>
    </div>

    <div class="card">
        @if($anyClaimable)
            <div class="reward-ready">🎉 Ada hadiah yang bisa ditukar!</div>
        @endif

        <div class="stamps">
            @for($i = 1; $i <= $size; $i++)
                @php $isM = isset($milestones[$i]); $isF = $i <= $current; @endphp
                <div class="stamp {{ $isF ? 'filled' : '' }} {{ $isM ? 'milestone' : '' }}">{{ $isM ? '🎁' : ($isF ? '★' : $i) }}</div>
            @endfor
        </div>

        {{-- Beri stempel --}}
        <form action="{{ route('kasir.stamp', $customer) }}" method="POST">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <label for="amount">Jumlah stempel</label>
            <input type="number" id="amount" name="amount" value="1" min="1" max="50">
            <button type="submit" class="btn gold mt">➕ Beri Stempel</button>
        </form>

        @if($current >= $size)
            <form action="{{ route('kasir.reset', $customer) }}" method="POST"
                  onsubmit="return confirm('Mulai kartu baru? Stempel kembali ke 0.');" class="mt">
                @csrf
                <button type="submit" class="btn secondary">🔄 Mulai Kartu Baru</button>
            </form>
        @endif
    </div>

    {{-- Hadiah --}}
    @if(count($rewardStatuses) > 0)
        <div class="card">
            <h2>Hadiah</h2>
            @foreach($rewardStatuses as $s)
                @php($r = $s['reward'])
                <div class="rwd">
                    @if($r->image_url)
                        <img src="{{ $r->image_url }}" alt="">
                    @else
                        <div class="ph">🎁</div>
                    @endif
                    <div class="info">
                        <b>{{ $r->name }}</b>
                        <span class="muted">Stempel ke-{{ $r->milestone }}@if($r->terms) &middot; {{ $r->terms }}@endif</span>
                    </div>
                    <div>
                        @if($s['claimed'])
                            <span class="badge grey">Sudah ditukar</span>
                        @elseif($s['claimable'])
                            <form action="{{ route('kasir.redeem', $customer) }}" method="POST" class="redeem-form" data-reward="{{ $r->name }}">
                                @csrf
                                <input type="hidden" name="reward_id" value="{{ $r->id }}">
                                <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <button type="button" class="btn sm gold" onclick="askRedeem(this)">Tukar</button>
                            </form>
                        @else
                            <span class="badge grey">🔒 {{ $r->milestone - $current }} lagi</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <a href="{{ route('kasir') }}" class="btn secondary">← Pelanggan berikutnya</a>

    {{-- Modal konfirmasi tukar hadiah --}}
    <style>
        .modal-overlay { position:fixed; inset:0; background:rgba(15,36,68,.55); display:none; align-items:center; justify-content:center; z-index:100; padding:24px; }
        .modal-overlay.show { display:flex; }
        .modal { background:#fff; border-radius:22px; padding:26px 22px 22px; max-width:360px; width:100%; text-align:center; box-shadow:0 24px 60px rgba(0,0,0,.32); animation:pop .18s ease; }
        @keyframes pop { from { transform:scale(.92); opacity:0; } to { transform:scale(1); opacity:1; } }
        .modal .mic { width:66px; height:66px; border-radius:50%; background:var(--grad-gold); display:flex; align-items:center; justify-content:center; font-size:34px; margin:0 auto 16px; box-shadow:0 8px 18px rgba(246,185,49,.4); }
        .modal h3 { font-size:18px; font-weight:800; margin:0 0 10px; }
        .modal p { font-size:14px; color:var(--muted); line-height:1.55; margin:0 0 22px; }
        .modal-actions { display:flex; gap:10px; }
        .modal-actions .btn { flex:1; }
    </style>
    <div class="modal-overlay" id="redeemModal" onclick="if(event.target===this)closeRedeem()">
        <div class="modal">
            <div class="mic">🎁</div>
            <h3 id="redeemTitle">Tandai sudah menukar hadiah?</h3>
            <p>Tindakan ini <b>tidak dapat dibatalkan</b>. Pastikan kamu sudah menukarkan hadiah di depan kasir.</p>
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeRedeem()">Batal</button>
                <button type="button" class="btn gold" onclick="confirmRedeem()">Ya, Tukar</button>
            </div>
        </div>
    </div>
    <script>
        var _redeemForm = null;
        function askRedeem(btn) {
            _redeemForm = btn.closest('form');
            document.getElementById('redeemTitle').textContent = 'Tandai sudah menukar ' + _redeemForm.dataset.reward + '?';
            document.getElementById('redeemModal').classList.add('show');
        }
        function closeRedeem() {
            document.getElementById('redeemModal').classList.remove('show');
            _redeemForm = null;
        }
        function confirmRedeem() {
            if (_redeemForm) _redeemForm.submit();
        }
    </script>
@endsection

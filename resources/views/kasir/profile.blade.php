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
                            <form action="{{ route('kasir.redeem', $customer) }}" method="POST"
                                  onsubmit="return confirm('Tandai Sudah menukar {{ addslashes($r->name) }}?\n\nTindakan ini tidak dapat dibatalkan, pastikan kamu menukarkan hadiah kamu di depan kasir.');">
                                @csrf
                                <input type="hidden" name="reward_id" value="{{ $r->id }}">
                                <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <button type="submit" class="btn sm gold">Tukar</button>
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
@endsection

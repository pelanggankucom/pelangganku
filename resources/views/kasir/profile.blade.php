@extends('layouts.app')
@section('title', $customer->name . ' · Profil')

@section('content')
    @php
        $per = $program?->stamps_per_reward ?? 10;
        $current = $balance?->stamps_current ?? 0;
        $filled = min($current, $per);
    @endphp

    <h1>{{ $customer->name }}</h1>
    <p class="sub">{{ $customer->phone_masked }} &middot; {{ $current }} stempel</p>

    <div class="card">
        @if($current >= $per)
            <div class="reward-ready">🎉 Pelanggan berhak menukar hadiah!</div>
        @endif

        <div style="font-size:14px; color:var(--muted)">Progres: {{ $filled }}/{{ $per }}</div>
        <div class="stamps">
            @for($i = 1; $i <= $per; $i++)
                <div class="stamp {{ $i <= $filled ? 'filled' : '' }}">{{ $i <= $filled ? '★' : $i }}</div>
            @endfor
        </div>

        {{-- Beri stempel --}}
        <form action="{{ route('kasir.stamp', $customer) }}" method="POST">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
            <label for="amount">Jumlah stempel</label>
            <input type="number" id="amount" name="amount" value="1" min="1" max="50">
            <button type="submit" class="btn mt">➕ Beri Stempel</button>
        </form>
    </div>

    {{-- Tukar hadiah --}}
    @if($rewards->isNotEmpty())
        <div class="card mt">
            <div style="font-size:14px; color:var(--muted); margin-bottom:10px">Tukar Hadiah</div>
            @foreach($rewards as $reward)
                <form action="{{ route('kasir.redeem', $customer) }}" method="POST" class="mt"
                      onsubmit="return confirm('Tukar hadiah: {{ $reward->name }}?');">
                    @csrf
                    <input type="hidden" name="reward_id" value="{{ $reward->id }}">
                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <button type="submit" class="btn {{ $current >= $reward->cost_stamps ? '' : 'secondary' }}"
                        {{ $current >= $reward->cost_stamps ? '' : 'disabled' }}>
                        🎁 {{ $reward->name }} ({{ $reward->cost_stamps }} stempel)
                    </button>
                </form>
            @endforeach
        </div>
    @endif

    <a href="{{ route('kasir') }}" class="btn secondary mt">← Pelanggan berikutnya</a>
@endsection

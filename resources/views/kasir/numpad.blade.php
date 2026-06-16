@extends('layouts.app')
@section('title', 'Kasir · pelangganku.com')

@section('content')
    <h1>Masukkan No. HP Pelanggan</h1>
    <p class="sub">
        @if($program)
            Kartu {{ $program->card_size }} stempel.
        @else
            Program loyalitas belum diatur owner.
        @endif
    </p>

    <form action="{{ route('kasir.lookup') }}" method="POST" id="numForm">
        @csrf
        <input type="hidden" name="phone" id="phone">
        <div class="display" id="display">0…</div>

        <div class="grid">
            @foreach(['1','2','3','4','5','6','7','8','9'] as $n)
                <button type="button" class="key" data-key="{{ $n }}">{{ $n }}</button>
            @endforeach
            <button type="button" class="key" data-action="clear" style="font-size:18px; color:var(--muted)">Hapus</button>
            <button type="button" class="key" data-key="0">0</button>
            <button type="button" class="key" data-action="back" style="font-size:22px">⌫</button>
        </div>

        <button type="submit" class="btn mt" id="cekBtn" disabled>Cek Pelanggan</button>
    </form>

    <script>
        (function () {
            var val = '';
            var display = document.getElementById('display');
            var phone = document.getElementById('phone');
            var cek = document.getElementById('cekBtn');

            function render() {
                display.textContent = val === '' ? '0…' : val.replace(/(\d{4})(\d{0,4})(\d{0,5})/, function (_, a, b, c) {
                    return [a, b, c].filter(Boolean).join('-');
                });
                phone.value = val;
                cek.disabled = val.length < 9;
            }

            document.querySelectorAll('.key').forEach(function (k) {
                k.addEventListener('click', function () {
                    var act = k.getAttribute('data-action');
                    if (act === 'clear') { val = ''; }
                    else if (act === 'back') { val = val.slice(0, -1); }
                    else if (val.length < 15) { val += k.getAttribute('data-key'); }
                    render();
                });
            });
            render();
        })();
    </script>
@endsection

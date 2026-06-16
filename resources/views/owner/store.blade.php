@extends('layouts.app')
@section('title', 'Profil Toko')

@section('content')
    <h1>Profil Toko</h1>
    <p class="sub">Logo, foto, alamat, dan media sosial.</p>

    <form action="{{ route('owner.store.update') }}" method="POST" enctype="multipart/form-data" class="card">
        @csrf

        <div class="row" style="align-items:center; gap:16px; margin-bottom:8px">
            <div class="rwd" style="padding:0">
                @if($merchant->logo_url)
                    <img src="{{ $merchant->logo_url }}" alt="logo">
                @else
                    <div class="ph">🏪</div>
                @endif
            </div>
            <div style="flex:1">
                <label style="margin-top:0">Logo Toko</label>
                <input type="file" name="logo" accept="image/*">
            </div>
        </div>

        <label>Foto Toko (banner)</label>
        @if($merchant->photo_url)
            <img src="{{ $merchant->photo_url }}" alt="foto" style="width:100%; border-radius:10px; margin-bottom:8px">
        @endif
        <input type="file" name="photo" accept="image/*">

        <label for="name">Nama Toko</label>
        <input type="text" id="name" name="name" value="{{ old('name', $merchant->name) }}" required>

        <label for="address">Alamat</label>
        <textarea id="address" name="address">{{ old('address', $merchant->address) }}</textarea>

        <label for="phone">No. Telepon Toko</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $merchant->phone) }}">

        <label for="instagram">Instagram</label>
        <input type="text" id="instagram" name="instagram" value="{{ old('instagram', $merchant->instagram) }}" placeholder="@tokokamu">

        <label for="whatsapp">WhatsApp</label>
        <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $merchant->whatsapp) }}" placeholder="08xxxx">

        <label for="facebook">Facebook</label>
        <input type="text" id="facebook" name="facebook" value="{{ old('facebook', $merchant->facebook) }}">

        <label for="tiktok">TikTok</label>
        <input type="text" id="tiktok" name="tiktok" value="{{ old('tiktok', $merchant->tiktok) }}">

        <label for="website">Website</label>
        <input type="text" id="website" name="website" value="{{ old('website', $merchant->website) }}">

        <button type="submit" class="btn mt">Simpan Profil</button>
    </form>

    <a href="{{ route('owner.settings') }}" class="btn secondary">← Kembali ke Atur</a>
@endsection

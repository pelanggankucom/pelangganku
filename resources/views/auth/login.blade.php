@extends('layouts.app')
@section('title', 'Masuk · pelangganku.com')

@section('content')
    <div style="text-align:center; margin:34px 0 22px;">
        <img src="/logo.svg" alt="pelangganku" style="width:72px; height:72px;">
        <h1 style="margin-top:12px;">pelangganku<span style="color:var(--gold)">.</span>com</h1>
        <p class="sub" style="margin-bottom:0;">Loyalty That Matters</p>
    </div>

    <form action="{{ route('login') }}" method="POST" class="card">
        @csrf
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit" class="btn mt">Masuk</button>
    </form>

    <p class="sub" style="text-align:center; margin-top:18px; font-size:12px;">
        Demo: owner@pelangganku.com / kasir@pelangganku.com — password: <b>password</b>
    </p>
@endsection

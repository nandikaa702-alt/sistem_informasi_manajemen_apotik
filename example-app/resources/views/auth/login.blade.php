@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <h1>Masuk</h1>
    <p class="subtitle">Gunakan akun Anda untuk melanjutkan ke sistem apotek.</p>
    @if (session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
    <form action="{{ route('login.store') }}" method="POST">
        @csrf
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        <label for="password">Kata sandi</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <label class="remember"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
        <button class="button" type="submit">Masuk</button>
    </form>
    @if ($needsSetup)
        <div class="auth-footer"><a href="{{ route('setup') }}">Buat akun admin pertama</a></div>
    @endif
@endsection
@extends('layouts.auth')

@section('title', 'Setup Admin')

@section('content')
    <h1>Siapkan admin</h1>
    <p class="subtitle">Buat akun admin pertama. Pendaftaran setup akan ditutup setelah akun dibuat.</p>
    @if ($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
    <form action="{{ route('setup.store') }}" method="POST">
        @csrf
        <label for="name">Nama lengkap</label>
        <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        <label for="password">Kata sandi (minimal 10 karakter)</label>
        <input id="password" name="password" type="password" autocomplete="new-password" required>
        <label for="password_confirmation">Ulangi kata sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        <button class="button" type="submit">Buat akun admin</button>
    </form>
    <div class="auth-footer"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></div>
@endsection
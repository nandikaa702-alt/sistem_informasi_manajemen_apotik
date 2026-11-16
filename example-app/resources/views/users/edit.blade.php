@extends('layouts.app')

@section('title', 'Ubah Pengguna')

@section('content')
    <header class="page-heading"><div><h1>Ubah Pengguna</h1><p class="subtitle">Perbarui akun {{ $user->name }}.</p></div></header>
    @if ($errors->any())<div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>@endif
    <section class="panel form-panel">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            @include('users._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('users.index') }}">Batal</a>
                <button class="button" type="submit">Simpan perubahan</button>
            </div>
        </form>
    </section>
@endsection
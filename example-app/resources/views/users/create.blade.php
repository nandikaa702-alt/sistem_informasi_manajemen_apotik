@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
    <header class="page-heading"><div><h1>Tambah Pengguna</h1><p class="subtitle">Buat akun dengan tingkat akses yang sesuai.</p></div></header>
    @if ($errors->any())<div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>@endif
    <section class="panel form-panel">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            @include('users._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('users.index') }}">Batal</a>
                <button class="button" type="submit">Simpan pengguna</button>
            </div>
        </form>
    </section>
@endsection
@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <header class="page-heading">
        <div><h1>Tambah Kategori</h1><p class="subtitle">Buat kategori untuk pengelompokan obat.</p></div>
    </header>
    @if ($errors->any())
        <div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>
    @endif
    <section class="panel form-panel">
        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            @include('categories._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('categories.index') }}">Batal</a>
                <button class="button" type="submit">Simpan kategori</button>
            </div>
        </form>
    </section>
@endsection
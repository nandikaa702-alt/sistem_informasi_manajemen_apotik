@extends('layouts.app')

@section('title', 'Ubah Kategori')

@section('content')
    <header class="page-heading">
        <div><h1>Ubah Kategori</h1><p class="subtitle">Perbarui kategori {{ $category->name }}.</p></div>
    </header>
    @if ($errors->any())
        <div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>
    @endif
    <section class="panel form-panel">
        <form action="{{ route('categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            @include('categories._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('categories.index') }}">Batal</a>
                <button class="button" type="submit">Simpan perubahan</button>
            </div>
        </form>
    </section>
@endsection
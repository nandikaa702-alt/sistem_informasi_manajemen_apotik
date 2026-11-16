@extends('layouts.app')

@section('title', 'Tambah Obat')

@section('content')
    <header class="page-heading">
        <div><h1>Tambah Obat</h1><p class="subtitle">Lengkapi informasi obat untuk ditambahkan ke persediaan.</p></div>
    </header>
    @if ($errors->any())
        <div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>
    @endif
    <section class="panel form-panel">
        <form action="{{ route('medicines.store') }}" method="POST">
            @csrf
            @include('medicines._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('medicines.index') }}">Batal</a>
                <button class="button" type="submit">Simpan obat</button>
            </div>
        </form>
    </section>
@endsection
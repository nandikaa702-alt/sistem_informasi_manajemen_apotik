@extends('layouts.app')

@section('title', 'Ubah Obat')

@section('content')
    <header class="page-heading">
        <div><h1>Ubah Obat</h1><p class="subtitle">Perbarui informasi {{ $medicine->name }}.</p></div>
    </header>
    @if ($errors->any())
        <div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>
    @endif
    <section class="panel form-panel">
        <form action="{{ route('medicines.update', $medicine) }}" method="POST">
            @csrf
            @method('PUT')
            @include('medicines._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('medicines.index') }}">Batal</a>
                <button class="button" type="submit">Simpan perubahan</button>
            </div>
        </form>
    </section>
@endsection
@extends('layouts.app')

@section('title', 'Tambah Pemasok')

@section('content')
    <header class="page-heading">
        <div><h1>Tambah Pemasok</h1><p class="subtitle">Masukkan informasi kontak dan perusahaan pemasok.</p></div>
    </header>
    @if ($errors->any())<div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>@endif
    <section class="panel form-panel">
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf
            @include('suppliers._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('suppliers.index') }}">Batal</a>
                <button class="button" type="submit">Simpan pemasok</button>
            </div>
        </form>
    </section>
@endsection
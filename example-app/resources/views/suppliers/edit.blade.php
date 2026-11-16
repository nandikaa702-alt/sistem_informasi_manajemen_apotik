@extends('layouts.app')

@section('title', 'Ubah Pemasok')

@section('content')
    <header class="page-heading">
        <div><h1>Ubah Pemasok</h1><p class="subtitle">Perbarui informasi {{ $supplier->name }}.</p></div>
    </header>
    @if ($errors->any())<div class="error-summary" role="alert">Periksa kembali {{ $errors->count() }} isian yang ditandai.</div>@endif
    <section class="panel form-panel">
        <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
            @csrf
            @method('PUT')
            @include('suppliers._form')
            <div class="form-actions">
                <a class="button secondary" href="{{ route('suppliers.index') }}">Batal</a>
                <button class="button" type="submit">Simpan perubahan</button>
            </div>
        </form>
    </section>
@endsection
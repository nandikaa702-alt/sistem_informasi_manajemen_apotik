@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <header class="page-heading">
        <div><h1>Manajemen Pengguna</h1><p class="subtitle">Atur akun dan tingkat akses pengguna aplikasi.</p></div>
        <a class="button" href="{{ route('users.create') }}">Tambah pengguna</a>
    </header>
    <div class="toolbar">
        <form class="search" action="{{ route('users.index') }}" method="GET" role="search">
            <label for="search" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Cari pengguna</label>
            <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Cari nama atau email...">
            <button class="button secondary" type="submit">Cari</button>
            @if ($search !== '')<a class="button secondary" href="{{ route('users.index') }}">Reset</a>@endif
        </form>
        <div class="count">{{ number_format($users->total(), 0, ',', '.') }} pengguna</div>
    </div>
    <section class="panel table-wrap">
        @if ($users->isEmpty())
            <div class="empty">Tidak ada pengguna yang cocok.</div>
        @else
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="medicine-name">{{ $user->name }} @if ($user->is(auth()->user()))<span class="description">Akun Anda</span>@endif</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ['admin' => 'Admin', 'apoteker' => 'Apoteker', 'kasir' => 'Kasir'][$user->role] ?? $user->role }}</td>
                            <td>
                                <div class="row-actions">
                                    <a class="button secondary small" href="{{ route('users.edit', $user) }}">Ubah</a>
                                    @if (!$user->is(auth()->user()))
                                        <form class="inline-form" action="{{ route('users.destroy', $user) }}" method="POST" data-confirm="Hapus akun {{ $user->name }}?" onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button danger small" type="submit">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($users->hasPages())<div class="pagination">{{ $users->links() }}</div>@endif
        @endif
    </section>
@endsection
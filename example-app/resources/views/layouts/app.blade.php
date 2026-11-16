<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f6f1">
    <title>@yield('title', 'Apotek Sehat') | Apotek Sehat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #1d2924; --muted: #78817b; --line: #e7eae4; --paper: #fff; --canvas: #f4f6f1; --green: #245c46; --green-soft: #eaf3ed; --red: #a34a3c; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--canvas); color: var(--ink); font: 14px 'DM Sans', sans-serif; }
        a { color: inherit; }
        .app { display: grid; grid-template-columns: 220px minmax(0, 1fr); min-height: 100vh; }
        .sidebar { display: flex; flex-direction: column; padding: 26px 16px; background: var(--paper); border-right: 1px solid var(--line); }
        .brand { display: flex; align-items: center; gap: 10px; padding: 0 9px 29px; text-decoration: none; }
        .brand-mark { display: grid; width: 35px; height: 35px; place-items: center; border-radius: 10px; background: var(--green); color: #fff; font: 800 17px 'Manrope', sans-serif; }
        .brand-name { font: 800 14px 'Manrope', sans-serif; }
        .brand-caption { margin-top: 3px; color: var(--muted); font-size: 10px; }
        .nav-label { padding: 0 10px; color: #99a19b; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .nav-link { display: flex; min-height: 41px; align-items: center; margin-top: 7px; padding: 0 11px; border-radius: 6px; color: #68736c; font-weight: 600; text-decoration: none; }
        .nav-link.active, .nav-link:hover { background: var(--green-soft); color: var(--green); }
        .nav-link { cursor: pointer; }
        .sidebar-footer { margin-top: auto; padding: 14px 10px 0; border-top: 1px solid var(--line); }
        .user-summary { margin-bottom: 10px; color: #536057; font-size: 11px; }
        .user-summary strong { display: block; color: var(--ink); font-size: 12px; }
        .logout-button { width: 100%; justify-content: flex-start; }
        main { min-width: 0; padding: 25px clamp(16px, 3vw, 42px) 36px; }
        .topbar { display: flex; justify-content: flex-end; max-width: 1320px; margin: 0 auto 29px; color: var(--muted); font-size: 12px; }
        .content { max-width: 1320px; margin: 0 auto; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        h1, h2, p { margin: 0; }
        h1 { font: 800 25px/1.2 'Manrope', sans-serif; }
        h2 { font: 700 15px/1.35 'Manrope', sans-serif; }
        .subtitle { margin-top: 6px; color: var(--muted); font-size: 12px; }
        .button { display: inline-flex; min-height: 39px; align-items: center; justify-content: center; gap: 8px; padding: 0 13px; border: 1px solid transparent; border-radius: 6px; background: var(--green); color: #fff; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button:hover { background: #194b37; }
        .button.secondary { border-color: var(--line); background: #fff; color: #4b5850; }
        .button.danger { border-color: #f0d7d1; background: #fff; color: var(--red); }
        .button.small { min-height: 31px; padding: 0 9px; font-size: 11px; }
        .panel { border: 1px solid var(--line); border-radius: 7px; background: var(--paper); }
        .flash { margin-bottom: 15px; padding: 11px 13px; border: 1px solid #d5e7d8; border-radius: 6px; background: #edf5ee; color: #315d3b; }
        .error-summary { margin-bottom: 15px; padding: 11px 13px; border: 1px solid #f0d7d1; border-radius: 6px; background: #fbefec; color: var(--red); }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .search { display: flex; width: min(100%, 440px); gap: 8px; }
        input, select, textarea { width: 100%; min-height: 39px; padding: 9px 10px; border: 1px solid #dce1db; border-radius: 5px; background: #fff; color: var(--ink); font: inherit; }
        input:focus, select:focus, textarea:focus { border-color: #5d8a70; outline: 3px solid #e6f0e8; }
        label { display: block; margin-bottom: 6px; color: #4f5b53; font-size: 12px; font-weight: 700; }
        textarea { min-height: 92px; resize: vertical; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; }
        th { padding: 11px 13px; border-bottom: 1px solid var(--line); background: #fafbf9; color: #7d8780; font-size: 10px; text-transform: uppercase; }
        td { padding: 11px 13px; border-bottom: 1px solid #eff1ed; color: #4e5a52; font-size: 12px; }
        tbody tr:last-child td { border-bottom: 0; }
        .medicine-name { color: var(--ink); font-weight: 700; }
        .description { max-width: 230px; margin-top: 4px; overflow: hidden; color: var(--muted); font-size: 10px; text-overflow: ellipsis; }
        .row-actions { display: flex; align-items: center; gap: 6px; }
        .inline-form { display: inline; }
        .empty { padding: 38px 16px; color: var(--muted); text-align: center; }
        .pagination { padding: 14px; border-top: 1px solid var(--line); }
        .form-panel { padding: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .field.full { grid-column: 1 / -1; }
        .field-error { margin-top: 5px; color: var(--red); font-size: 11px; }
        .form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--line); }
        .count { color: var(--muted); font-size: 11px; }
        @media (max-width: 760px) {
            .app { display: block; }
            .sidebar { padding: 13px 15px; border-right: 0; border-bottom: 1px solid var(--line); }
            .brand { padding: 0 2px 12px; }
            .nav-label { display: none; }
            .nav-link { display: inline-flex; min-height: 34px; margin: 0 5px 0 0; }
            .sidebar nav { display: flex; flex-wrap: wrap; gap: 4px; }
            main { padding: 20px 14px 28px; }
            .topbar { margin-bottom: 23px; }
            .page-heading { align-items: flex-start; }
        }
        @media (max-width: 520px) {
            .page-heading { flex-direction: column; }
            .form-grid { grid-template-columns: 1fr; gap: 13px; }
            .field.full { grid-column: auto; }
            .toolbar { align-items: stretch; flex-direction: column; }
            .search { width: 100%; }
            .form-panel { padding: 15px; }
        }
    </style>
    <style>
        :root {
            --ink: #18352d;
            --muted: #647a72;
            --line: #dce8e4;
            --paper: #ffffff;
            --canvas: #f3f7f8;
            --green: #17684f;
            --green-soft: #e4f2ec;
            --blue: #2867dc;
            --blue-soft: #eaf1ff;
            --red: #a34a3c;
        }
        body { background: var(--canvas); color: var(--ink); }
        .app { grid-template-columns: 252px minmax(0, 1fr); }
        .sidebar { position: sticky; top: 0; height: 100vh; padding: 22px 15px 16px; overflow-y: auto; background: #123f36; border-right: 0; color: #f2faf6; }
        .brand { min-height: 48px; gap: 12px; padding: 0 9px 22px; color: #fff; }
        .brand-mark { width: 38px; height: 38px; border: 1px solid rgba(255,255,255,.18); border-radius: 11px; background: #fff; color: var(--green); }
        .brand-name { color: #fff; font-size: 15px; }
        .brand-caption { color: #afc8bd; }
        .nav-label { padding: 4px 11px 8px; color: #8eada0; }
        .sidebar nav { display: grid; gap: 3px; }
        .nav-link { min-height: 42px; gap: 11px; margin: 0; padding: 0 11px; border: 1px solid transparent; color: #c5d8cf; font-size: 13px; }
        .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav-link.active { border-color: rgba(91,145,255,.25); background: rgba(40,103,220,.2); color: #fff; box-shadow: inset 3px 0 0 #75a4ff; }
        .nav-icon { width: 17px; height: 17px; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .sidebar-footer { margin-top: auto; padding: 14px 9px 0; border-top-color: rgba(255,255,255,.14); }
        .user-summary { color: #a9c1b6; }
        .user-summary strong { color: #fff; }
        .logout-button { border-color: rgba(255,255,255,.2) !important; background: transparent !important; color: #e4f1eb !important; }
        .logout-button:hover { background: rgba(255,255,255,.1) !important; }
        main { padding: 0 clamp(18px, 3vw, 42px) 36px; }
        .topbar { position: sticky; top: 0; z-index: 20; display: flex; min-height: 72px; align-items: center; justify-content: space-between; gap: 18px; max-width: none; margin: 0 calc(-1 * clamp(18px, 3vw, 42px)) 27px; padding: 12px clamp(18px, 3vw, 42px); border-bottom: 1px solid var(--line); background: rgba(255,255,255,.96); }
        .topbar-context { min-width: 0; }
        .topbar-kicker { color: var(--blue); font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .topbar-title { display: block; margin-top: 3px; overflow: hidden; font: 700 14px 'Manrope', sans-serif; text-overflow: ellipsis; white-space: nowrap; }
        .topbar-right { display: flex; align-items: center; gap: 15px; }
        .topbar-date { color: var(--muted); font-size: 11px; white-space: nowrap; }
        .topbar-account { display: flex; align-items: center; gap: 9px; }
        .topbar-avatar { display: grid; width: 34px; height: 34px; place-items: center; border: 1px solid #d5e2ff; border-radius: 50%; background: var(--blue-soft); color: var(--blue); font-weight: 800; }
        .topbar-account strong { display: block; font-size: 11px; }
        .topbar-account small { color: var(--muted); font-size: 10px; }
        .topbar-logout { display: none; }
        .icon-button { display: grid; width: 35px; height: 35px; place-items: center; border: 1px solid #d4dfeb; border-radius: 5px; background: #fff; color: var(--blue); cursor: pointer; }
        .icon-button:hover { background: var(--blue-soft); }
        .icon-button svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .button { border-radius: 5px; background: var(--green); }
        .button:hover { background: #0e5740; }
        .button.secondary { border-color: #d4dfeb; background: #fff; color: #3b5f7c; }
        .button.secondary:hover { border-color: #b7cafa; background: var(--blue-soft); color: #2056bd; }
        .panel { border-color: var(--line); box-shadow: 0 4px 16px rgba(24,53,45,.035); }
        input, select, textarea { border-color: #d4e0df; }
        input:focus, select:focus, textarea:focus { border-color: var(--blue); outline-color: var(--blue-soft); }
        th { border-top-color: var(--line); background: #edf4f5; color: #526d75; letter-spacing: .03em; }
        td { color: #425b56; }
        .pagination a, .pagination span { color: var(--blue); }
        @media (max-width: 760px) {
            .app { display: block; }
            .sidebar { position: relative; display: flex; width: 100%; height: auto; padding: 12px 14px 10px; overflow: visible; }
            .brand { padding: 0 3px 10px; }
            .nav-label { display: none; }
            .sidebar nav { display: flex; flex-wrap: nowrap; gap: 4px; overflow-x: auto; padding-bottom: 5px; }
            .nav-link { flex: 0 0 auto; min-height: 35px; padding: 0 10px; font-size: 11px; }
            .sidebar-footer { display: none; }
            main { padding: 0 15px 28px; }
            .topbar { min-height: 61px; margin: 0 -15px 22px; padding: 10px 15px; }
            .topbar-date { display: none; }
            .topbar-logout { display: block; }
        }
        @media (max-width: 520px) {
            .topbar-account > span:last-child { display: none; }
            .topbar { gap: 8px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app">
        <aside class="sidebar" aria-label="Navigasi utama">
            @php($currentRole = auth()->user()->role)
            <a class="brand" href="{{ $currentRole === \App\Models\User::ROLE_KASIR ? route('sales.create') : route('dashboard') }}">
                <span class="brand-mark" aria-hidden="true">+</span>
                <span><span class="brand-name">Apotek Sehat</span><span class="brand-caption" style="display:block">Sistem Manajemen</span></span>
            </a>
            <div class="nav-label">Menu utama</div>
            <nav>
                @if (in_array($currentRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_APOTEKER], true))
                    <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><svg class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="11" width="7" height="10" rx="1.5"/><rect x="3" y="14" width="8" height="7" rx="1.5"/></svg>Dashboard</a>
                @endif
                <a class="nav-link {{ request()->routeIs('medicines.*') ? 'active' : '' }}" href="{{ route('medicines.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><path d="m9 3 12 12-6 6L3 9l6-6Z"/><path d="m7 11 4-4"/></svg>Data obat</a>
                @if (in_array($currentRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_APOTEKER], true))
                    <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><path d="M4 5h16M4 12h16M4 19h16"/><circle cx="8" cy="5" r="1"/><circle cx="16" cy="12" r="1"/><circle cx="10" cy="19" r="1"/></svg>Kategori obat</a>
                    <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01"/></svg>Pemasok</a>
                    <a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><path d="m3 7 9-4 9 4-9 4-9-4Z"/><path d="m3 12 9 4 9-4M3 17l9 4 9-4"/></svg>Manajemen stok</a>
                @endif
                <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><path d="M5 3h14v18l-3-2-4 2-4-2-3 2V3Z"/><path d="M8 8h8M8 12h8M8 16h4"/></svg>Penjualan</a>
                @if (in_array($currentRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_APOTEKER], true))
                    <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><path d="M3 7h18l-2 13H5L3 7Z"/><path d="M8 7a4 4 0 0 1 8 0"/></svg>Pembelian</a>
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><path d="M4 19V5m0 14h17"/><path d="M8 15v-4m5 4V7m5 8v-6"/></svg>Laporan</a>
                @endif
                @if ($currentRole === \App\Models\User::ROLE_ADMIN)
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><svg class="nav-icon" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M17 11a3 3 0 1 0-1-5.8M18 14a5 5 0 0 1 3 5v1"/></svg>Manajemen pengguna</a>
                @endif
            </nav>
            <div class="sidebar-footer">
                <div class="user-summary"><strong>{{ auth()->user()->name }}</strong>{{ ucfirst($currentRole) }}</div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="button secondary logout-button" type="submit">Keluar</button>
                </form>
            </div>
        </aside>
        <main>
            <header class="topbar">
                <div class="topbar-context"><span class="topbar-kicker">Apotek Sehat</span><strong class="topbar-title">@yield('title', 'Dashboard')</strong></div>
                <div class="topbar-right">
                    <span class="topbar-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                    <span class="topbar-account"><span class="topbar-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst($currentRole) }}</small></span></span>
                    <form class="topbar-logout" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="icon-button" type="submit" title="Keluar" aria-label="Keluar"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg></button>
                    </form>
                </div>
            </header>
            <div class="content">
                @if (session('status'))
                    <div class="flash" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="error-summary" role="alert">{{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f6f1">
    <title>@yield('title') | Apotek Sehat</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#1d2924; --muted:#78817b; --line:#e2e8e1; --green:#245c46; --canvas:#f4f6f1; --red:#a34a3c; }
        * { box-sizing:border-box; }
        body { display:grid; min-height:100vh; place-items:center; margin:0; padding:22px; background:radial-gradient(ellipse at 12% 8%,#e0eadf 0,transparent 34%),radial-gradient(ellipse at 90% 92%,#e5ece5 0,transparent 38%),var(--canvas); color:var(--ink); font:14px 'DM Sans',sans-serif; }
        .auth-shell { width:min(100%,430px); }
        .auth-brand { display:flex; align-items:center; justify-content:center; gap:11px; margin-bottom:20px; }
        .brand-mark { display:grid; width:38px; height:38px; place-items:center; border-radius:11px; background:var(--green); color:#fff; font:800 19px 'Manrope',sans-serif; }
        .brand-title { font:800 16px 'Manrope',sans-serif; }
        .brand-caption { margin-top:3px; color:var(--muted); font-size:11px; }
        .auth-panel { padding:26px; border:1px solid var(--line); border-radius:8px; background:#fff; box-shadow:0 12px 35px rgba(34,52,43,.07); }
        h1 { margin:0; font:800 22px 'Manrope',sans-serif; }
        .subtitle { margin:7px 0 21px; color:var(--muted); font-size:12px; line-height:1.55; }
        label { display:block; margin-bottom:6px; color:#4f5b53; font-size:12px; font-weight:700; }
        input,select { width:100%; min-height:42px; margin-bottom:15px; padding:10px 11px; border:1px solid #dce1db; border-radius:5px; background:#fff; color:var(--ink); font:inherit; }
        input:focus,select:focus { border-color:#5d8a70; outline:3px solid #e6f0e8; }
        .button { width:100%; min-height:42px; border:0; border-radius:5px; background:var(--green); color:white; font:700 13px 'DM Sans',sans-serif; cursor:pointer; }
        .button:hover { background:#194b37; }
        .error, .status { margin-bottom:15px; padding:10px 11px; border-radius:5px; font-size:12px; }
        .error { border:1px solid #f0d7d1; background:#fbefec; color:var(--red); }
        .status { border:1px solid #d5e7d8; background:#edf5ee; color:#315d3b; }
        .auth-footer { margin-top:15px; color:var(--muted); font-size:11px; text-align:center; }
        .auth-footer a { color:var(--green); font-weight:700; text-decoration:none; }
        .remember { display:flex; align-items:center; gap:8px; margin:0 0 16px; color:var(--muted); font-size:12px; }
        .remember input { width:15px; min-height:15px; margin:0; }
    </style>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-brand"><span class="brand-mark" aria-hidden="true">+</span><span><span class="brand-title">Apotek Sehat</span><span class="brand-caption" style="display:block">Sistem Informasi Manajemen</span></span></div>
        <main class="auth-panel">@yield('content')</main>
        <div class="auth-footer">Akses hanya untuk pengguna terdaftar.</div>
    </div>
</body>
</html>
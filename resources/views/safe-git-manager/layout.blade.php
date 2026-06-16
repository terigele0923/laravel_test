<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Git Manager</title>
    <style>
        :root { color-scheme: light; --border: #d9dee7; --muted: #64748b; --ink: #172033; --blue: #2563eb; }
        body { margin: 0; font-family: Arial, "Hiragino Sans", "Yu Gothic", sans-serif; color: var(--ink); background: #f7f8fb; }
        a { color: var(--blue); text-decoration: none; }
        a:hover { text-decoration: underline; }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; background: #fff; }
        th, td { border: 1px solid var(--border); padding: 10px; vertical-align: top; }
        th { background: #f1f5f9; text-align: left; font-weight: 700; }
        input, textarea, select { width: 100%; padding: 9px 10px; margin: 5px 0 14px; box-sizing: border-box; border: 1px solid #aeb7c5; border-radius: 4px; background: #fff; }
        input[type="checkbox"] { width: auto; margin: 0 6px 0 0; }
        button { padding: 9px 14px; cursor: pointer; border: 1px solid #aeb7c5; border-radius: 4px; background: #fff; }
        button.primary { background: var(--blue); border-color: var(--blue); color: #fff; }
        button.danger { background: #fff1f2; border-color: #fca5a5; color: #991b1b; }
        label { display: block; font-weight: 700; }
        pre { background: #111827; color: #e5e7eb; padding: 12px; overflow: auto; border-radius: 6px; white-space: pre-wrap; }
        code { font-family: Consolas, "Courier New", monospace; }
        .container { max-width: 1220px; margin: 0 auto; padding: 28px; }
        .nav { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
        .card { background: #fff; border: 1px solid var(--border); padding: 18px; margin: 16px 0; border-radius: 6px; }
        .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .alert { padding: 12px; margin: 12px 0; border-radius: 4px; }
        .success { background: #ecfdf5; color: #065f46; }
        .error { background: #fef2f2; color: #991b1b; }
        .notice { background: #eff6ff; color: #1e3a8a; }
        .muted { color: var(--muted); }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; background: #e2e8f0; color: #334155; }
        .badge.ok { background: #dcfce7; color: #166534; }
        .badge.ng { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <strong>Safe Git Manager</strong>
        <span class="muted">|</span>
        <a href="{{ route('safe-git.repositories.index') }}">リポジトリ一覧</a>
        <span class="muted">|</span>
        <a href="{{ route('safe-git.repositories.create') }}">新規登録</a>
    </div>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    @yield('content')
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Git Manager</title>
    <style>
        :root {
            color-scheme: light;
            --app-bg: #eef2f6;
            --surface: #ffffff;
            --surface-soft: #f7f9fc;
            --surface-strong: #e8edf4;
            --border: #d4dbe6;
            --border-strong: #b6c0cf;
            --ink: #172033;
            --muted: #667085;
            --blue: #2563eb;
            --blue-soft: #e8f0ff;
            --green: #0f766e;
            --red: #b42318;
            --red-soft: #fff1f2;
            --shadow: 0 1px 2px rgba(15, 23, 42, .06);
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, "Hiragino Sans", "Yu Gothic", sans-serif; color: var(--ink); background: var(--app-bg); font-size: 14px; }
        a { color: var(--blue); text-decoration: none; }
        a:hover { text-decoration: underline; }
        h1, h2, h3 { margin: 0; line-height: 1.25; }
        h1 { font-size: 22px; }
        h2 { font-size: 15px; }
        h3 { font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        p { margin: 8px 0; }
        code { font-family: Consolas, "Courier New", monospace; font-size: 13px; }
        pre { background: #111827; color: #e5e7eb; padding: 12px; overflow: auto; border-radius: 5px; white-space: pre-wrap; max-height: 340px; }

        input, textarea, select {
            width: 100%;
            padding: 8px 9px;
            margin: 5px 0 12px;
            border: 1px solid var(--border-strong);
            border-radius: 4px;
            background: #fff;
            color: var(--ink);
            font: inherit;
        }
        input[type="checkbox"] { width: auto; margin: 0 6px 0 0; }
        label { display: block; font-weight: 700; font-size: 13px; }
        button {
            padding: 8px 12px;
            cursor: pointer;
            border: 1px solid var(--border-strong);
            border-radius: 4px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            white-space: nowrap;
        }
        button.primary { background: var(--blue); border-color: var(--blue); color: #fff; }
        button.danger { background: var(--red-soft); border-color: #f3b4b8; color: var(--red); }
        button.compact { padding: 5px 8px; font-size: 12px; }

        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { border-bottom: 1px solid var(--border); padding: 9px 10px; vertical-align: middle; text-align: left; }
        th { background: var(--surface-soft); color: #475467; font-size: 12px; font-weight: 700; }
        tr:hover td { background: #fbfdff; }

        .topbar { height: 48px; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 0 20px; background: #243041; color: #fff; box-shadow: var(--shadow); }
        .topbar a { color: #dbeafe; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; }
        .brand-mark { width: 22px; height: 22px; border-radius: 5px; background: #4f8cff; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 13px; }
        .nav { display: flex; gap: 14px; align-items: center; font-size: 13px; }
        .container { max-width: 1480px; margin: 0 auto; padding: 18px; }

        .alert { padding: 11px 12px; margin: 0 0 12px; border-radius: 5px; border: 1px solid transparent; }
        .success { background: #ecfdf5; border-color: #bbf7d0; color: #065f46; }
        .error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .notice { background: #eff6ff; border-color: #bfdbfe; color: #1e3a8a; }
        .muted { color: var(--muted); }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; background: #e2e8f0; color: #334155; }
        .badge.ok { background: #dcfce7; color: #166534; }
        .badge.ng { background: #fee2e2; color: #991b1b; }
        .badge.current { background: var(--blue-soft); color: #1d4ed8; }

        .card, .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 6px; box-shadow: var(--shadow); }
        .card { padding: 16px; margin: 14px 0; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }

        .workbench { display: grid; grid-template-columns: 270px minmax(420px, 1fr) 360px; gap: 12px; align-items: start; }
        .workbench-header { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: center; margin-bottom: 12px; }
        .repo-title { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
        .repo-title code { color: var(--muted); overflow-wrap: anywhere; }
        .toolbar { display: flex; gap: 8px; align-items: center; justify-content: flex-end; flex-wrap: wrap; }
        .pane { overflow: hidden; }
        .pane-header { display: flex; justify-content: space-between; align-items: center; padding: 11px 12px; border-bottom: 1px solid var(--border); background: var(--surface-soft); }
        .pane-body { padding: 12px; }
        .sidebar-section + .sidebar-section { margin-top: 18px; }
        .branch-list { display: flex; flex-direction: column; gap: 4px; margin-top: 8px; }
        .branch-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 6px; align-items: center; padding: 7px 8px; border-radius: 5px; }
        .branch-row:hover { background: var(--surface-soft); }
        .branch-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .branch-actions { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
        .file-toolbar { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
        .file-name { word-break: break-all; }
        .right-stack { display: flex; flex-direction: column; gap: 12px; }
        .mini-form { margin: 0; }
        .split-note { font-size: 12px; color: var(--muted); }

        @media (max-width: 1120px) {
            .workbench { grid-template-columns: 240px minmax(0, 1fr); }
            .right-stack { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        }
        @media (max-width: 760px) {
            .container { padding: 12px; }
            .topbar { height: auto; align-items: flex-start; padding: 12px; flex-direction: column; }
            .workbench, .workbench-header { grid-template-columns: 1fr; }
            .toolbar { justify-content: flex-start; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand">
        <span class="brand-mark">G</span>
        <span>Safe Git Manager</span>
    </div>
    <div class="nav">
        <a href="{{ route('safe-git.repositories.index') }}">リポジトリ一覧</a>
        <a href="{{ route('safe-git.repositories.create') }}">新規登録</a>
    </div>
</div>

<div class="container">
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

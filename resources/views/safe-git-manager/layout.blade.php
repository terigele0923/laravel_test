<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Git Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        a { color: #2563eb; text-decoration: none; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        th { background: #f5f5f5; text-align: left; }
        input, textarea, select { width: 100%; padding: 8px; margin: 4px 0 12px; box-sizing: border-box; }
        button { padding: 8px 12px; cursor: pointer; }
        .container { max-width: 1200px; margin: 0 auto; }
        .nav { margin-bottom: 20px; }
        .alert { padding: 12px; margin-bottom: 16px; border-radius: 4px; }
        .success { background: #ecfdf5; color: #065f46; }
        .error { background: #fef2f2; color: #991b1b; }
        .card { border: 1px solid #ddd; padding: 16px; margin: 16px 0; border-radius: 6px; }
        .danger { color: #b91c1c; font-weight: bold; }
        pre { background: #111827; color: #e5e7eb; padding: 12px; overflow: auto; border-radius: 6px; }
        .actions form { display: inline-block; margin: 4px; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <strong>Safe Git Manager</strong> |
        <a href="{{ route('safe-git.repositories.index') }}">Repositories</a> |
        <a href="{{ route('safe-git.repositories.create') }}">New</a>
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

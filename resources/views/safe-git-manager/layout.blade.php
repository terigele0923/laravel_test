<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Git Manager</title>
    <link rel="stylesheet" href="{{ asset('css/safe-git-manager.css') }}">
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

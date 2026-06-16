@extends('safe-git-manager.layout')

@section('content')
@php
    $originRemote = $status['remotes']['origin'] ?? null;
    $originUrl = $originRemote['push'] ?? $originRemote['fetch'] ?? null;
    $selectedRemoteMode = old('mode', $originRemote ? 'set-url' : 'add');
    $files = $status['files'] ?? [];
@endphp

<h1>{{ $repository->name }}</h1>

<div class="grid">
    <div class="card">
        <h2>プロジェクト情報</h2>
        <p><strong>ローカル:</strong> <code>{{ $repository->local_path }}</code></p>
        <p><strong>ブランチ:</strong> {{ $status['branch'] ?? '-' }}</p>
        <p><strong>デフォルト:</strong> {{ $repository->default_branch }}</p>
    </div>

    <div class="card">
        <h2>Remote 状態</h2>
        @if($originRemote)
            <p><span class="badge ok">origin 設定済み</span></p>
            <p><strong>URL:</strong> {{ $originUrl }}</p>
            <p class="muted">URLを変更する時は remote set-url origin を使います。</p>
        @else
            <p><span class="badge ng">origin 未設定</span></p>
            <p class="muted">初回だけ remote add origin を使います。</p>
        @endif
    </div>
</div>

@if(isset($status['error']))
    <div class="alert error">{{ $status['error'] }}</div>
@endif

<div class="card">
    <h2>1. Remote 設定</h2>
    <form method="POST" action="{{ route('safe-git.repositories.remote', $repository) }}">
        @csrf
        <label>Remote URL</label>
        <input name="remote_url" value="{{ old('remote_url', $originUrl ?? $repository->remote_url) }}" placeholder="https://github.com/user/repo.git" required>

        <label>Mode</label>
        <select name="mode">
            <option value="add" @selected($selectedRemoteMode === 'add')>remote add origin（originが無い時）</option>
            <option value="set-url" @selected($selectedRemoteMode === 'set-url')>remote set-url origin（originがある時）</option>
        </select>

        <button type="submit">Remote設定</button>
    </form>
</div>

<div class="card">
    <h2>2. 変更ファイル</h2>
    <p class="muted">コミットしたいファイルを add します。まとめて追加する場合は「すべて add」を使えます。</p>

    <div class="actions">
        <form method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
            @csrf
            <input type="hidden" name="path" value=".">
            <button type="submit">すべて add</button>
        </form>
        <a href="{{ route('safe-git.repositories.diff', $repository) }}">diffを見る</a>
        <a href="{{ route('safe-git.repositories.logs', $repository) }}">操作ログ</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>状態</th>
                <th>ファイル</th>
                <th>ステージング</th>
            </tr>
        </thead>
        <tbody>
            @forelse($files as $file)
                <tr>
                    <td>{{ $file['status'] }}</td>
                    <td><code>{{ $file['path'] }}</code></td>
                    <td>
                        <form method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
                            @csrf
                            <input type="hidden" name="path" value="{{ $file['path'] }}">
                            <button type="submit">add</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">変更はありません。</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h2>3. Commit</h2>
    <form method="POST" action="{{ route('safe-git.repositories.commit', $repository) }}">
        @csrf
        <label>コミットメッセージ</label>
        <input name="message" placeholder="例: Safe Git Manager の表示を改善" required>
        <button type="submit" class="primary">commit</button>
    </form>
</div>

<div class="card">
    <h2>4. 同期</h2>
    <div class="actions">
        <form method="POST" action="{{ route('safe-git.repositories.fetch', $repository) }}">
            @csrf
            <button type="submit">fetch</button>
        </form>

        <form method="POST" action="{{ route('safe-git.repositories.pull', $repository) }}">
            @csrf
            <button type="submit">pull</button>
        </form>

        <form method="POST" action="{{ route('safe-git.repositories.push', $repository) }}">
            @csrf
            <label>
                <input type="checkbox" name="confirm_main_push" value="1">
                main/master への push を確認済み
            </label>
            <button type="submit" class="danger">push</button>
        </form>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2>Remote 情報</h2>
        <pre>{{ $status['remote'] ?? '-' }}</pre>
    </div>

    <div class="card">
        <h2>Log Tree</h2>
        <pre>{{ $graph ?: 'ログはありません。' }}</pre>
    </div>
</div>
@endsection

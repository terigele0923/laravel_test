@extends('safe-git-manager.layout')

@section('content')
<h1>{{ $repository->name }}</h1>

<div class="card">
    <p><strong>Local:</strong> {{ $repository->local_path }}</p>
    <p><strong>Remote:</strong> {{ $repository->remote_url ?: '-' }}</p>
    <p><strong>Default Branch:</strong> {{ $repository->default_branch }}</p>
</div>

@if(isset($status['error']))
    <div class="alert error">{{ $status['error'] }}</div>
@endif

<div class="card actions">
    <h2>基本操作</h2>

    <form method="POST" action="{{ route('safe-git.repositories.init', $repository) }}">
        @csrf
        <label><input type="checkbox" name="create_readme" value="1"> READMEを作成</label>
        <button type="submit">git init</button>
    </form>

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
        <label><input type="checkbox" name="confirm_main_push" value="1"> main/master pushを確認済み</label>
        <button type="submit">push</button>
    </form>

    <p>
        <a href="{{ route('safe-git.repositories.diff', $repository) }}">diffを見る</a> |
        <a href="{{ route('safe-git.repositories.logs', $repository) }}">操作ログ</a>
    </p>
</div>

<div class="card">
    <h2>Remote設定</h2>
    <form method="POST" action="{{ route('safe-git.repositories.remote', $repository) }}">
        @csrf
        <label>Remote URL</label>
        <input name="remote_url" value="{{ old('remote_url', $repository->remote_url) }}" placeholder="https://github.com/user/repo.git" required>
        <label>Mode</label>
        <select name="mode">
            <option value="add">remote add origin</option>
            <option value="set-url">remote set-url origin</option>
        </select>
        <button type="submit">Remote設定</button>
    </form>
</div>

<div class="card">
    <h2>作業ツリー</h2>
    <p><strong>現在ブランチ:</strong> {{ $status['branch'] ?? '-' }}</p>

    @if(!empty($status['conflicts']))
        <p class="danger">コンフリクト検出</p>
        <ul>
            @foreach($status['conflicts'] as $file)
                <li>{{ $file }}</li>
            @endforeach
        </ul>
    @endif

    <table>
        <thead>
            <tr>
                <th>状態</th>
                <th>ファイル</th>
                <th>ステージング</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($status['files'] ?? []) as $file)
                <tr>
                    <td>{{ $file['status'] }}</td>
                    <td>{{ $file['path'] }}</td>
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
    <h2>Commit</h2>
    <form method="POST" action="{{ route('safe-git.repositories.commit', $repository) }}">
        @csrf
        <label>コミットメッセージ</label>
        <input name="message" placeholder="fix: ログインエラーを修正" required>
        <button type="submit">commit</button>
    </form>
</div>

<div class="card">
    <h2>Remote情報</h2>
    <pre>{{ $status['remote'] ?? '-' }}</pre>
</div>

<div class="card">
    <h2>Log Tree</h2>
    <pre>{{ $graph ?: 'ログはありません。' }}</pre>
</div>
@endsection

@extends('safe-git-manager.layout')

@section('content')
@php
    $originRemote = $status['remotes']['origin'] ?? null;
    $originUrl = $originRemote['push'] ?? $originRemote['fetch'] ?? null;
    $selectedRemoteMode = old('mode', $originRemote ? 'set-url' : 'add');
    $files = $status['files'] ?? [];
    $branches = $status['branches'] ?? [];
    $currentBranch = $status['branch'] ?? '-';
    $mergeBranches = array_values(array_filter($branches, fn ($branch) => ! $branch['current']));
@endphp

<div class="actions" style="justify-content: space-between;">
    <h1>{{ $repository->name }}</h1>
    <form method="POST" action="{{ route('safe-git.repositories.destroy', $repository) }}" onsubmit="return confirm('この管理画面から登録を削除します。ローカルフォルダとGitHubリポジトリは削除されません。よろしいですか？');">
        @csrf
        @method('DELETE')
        <button type="submit" class="danger">この登録を削除</button>
    </form>
</div>

<div class="grid">
    <div class="card">
        <h2>プロジェクト情報</h2>
        <p><strong>ローカル:</strong> <code>{{ $repository->local_path }}</code></p>
        <p><strong>現在ブランチ:</strong> {{ $currentBranch }}</p>
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
    <h2>2. ブランチ管理</h2>
    <p class="muted">作業用ブランチを作成し、切り替え、不要になったブランチを削除できます。マージは現在ブランチへ取り込みます。</p>

    <div class="grid">
        <form method="POST" action="{{ route('safe-git.repositories.branches.create', $repository) }}">
            @csrf
            <label>新規ブランチ名</label>
            <input name="branch" placeholder="feature/example" required>
            <label><input type="checkbox" name="checkout" value="1" checked> 作成後に切り替える</label>
            <button type="submit" class="primary">ブランチ作成</button>
        </form>

        <form method="POST" action="{{ route('safe-git.repositories.branches.merge', $repository) }}">
            @csrf
            <label>現在ブランチへマージするブランチ</label>
            <select name="branch" required>
                @forelse($mergeBranches as $branch)
                    <option value="{{ $branch['name'] }}">{{ $branch['name'] }}</option>
                @empty
                    <option value="">マージできるブランチがありません</option>
                @endforelse
            </select>
            <button type="submit">現在ブランチへマージ</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ブランチ</th>
                <th>状態</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $branch)
                <tr>
                    <td><code>{{ $branch['name'] }}</code></td>
                    <td>
                        @if($branch['current'])
                            <span class="badge ok">現在</span>
                        @elseif($branch['protected'])
                            <span class="badge">保護対象</span>
                        @else
                            <span class="badge">ローカル</span>
                        @endif
                    </td>
                    <td>
                        <div class="actions">
                            @unless($branch['current'])
                                <form method="POST" action="{{ route('safe-git.repositories.branches.switch', $repository) }}">
                                    @csrf
                                    <input type="hidden" name="branch" value="{{ $branch['name'] }}">
                                    <button type="submit">切り替え</button>
                                </form>

                                <form method="POST" action="{{ route('safe-git.repositories.branches.delete', $repository) }}" onsubmit="return confirm('ローカルブランチ「{{ $branch['name'] }}」を削除します。よろしいですか？');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="branch" value="{{ $branch['name'] }}">
                                    @if($branch['protected'])
                                        <label><input type="checkbox" name="confirm_delete_protected" value="1"> main/master 削除を確認</label>
                                    @endif
                                    <button type="submit" class="danger">削除</button>
                                </form>
                            @else
                                <span class="muted">現在使用中です</span>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">ブランチ情報がありません。</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h2>3. 変更ファイル</h2>
    <p class="muted">コミットしたいファイルを add します。まとめて追加する場合は下のボタンを使います。</p>

    <div class="actions">
        <form method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
            @csrf
            <input type="hidden" name="path" value=".">
            <button type="submit" class="primary">変更をすべてステージング</button>
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
                            <button type="submit">このファイルを add</button>
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
    <h2>4. Commit</h2>
    <form method="POST" action="{{ route('safe-git.repositories.commit', $repository) }}">
        @csrf
        <label>コミットメッセージ</label>
        <input name="message" placeholder="例: Safe Git Manager の表示を改善" required>
        <button type="submit" class="primary">commit</button>
    </form>
</div>

<div class="card">
    <h2>5. 同期</h2>
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

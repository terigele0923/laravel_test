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

<div class="workbench-header">
    <div class="repo-title">
        <h1>{{ $repository->name }}</h1>
        <code>{{ $repository->local_path }}</code>
    </div>
    <div class="toolbar">
        <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.fetch', $repository) }}">
            @csrf
            <button type="submit">fetch</button>
        </form>
        <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.pull', $repository) }}">
            @csrf
            <button type="submit">pull</button>
        </form>
        <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.push', $repository) }}">
            @csrf
            <label class="split-note"><input type="checkbox" name="confirm_main_push" value="1"> main/master 確認</label>
            <button type="submit" class="danger">push</button>
        </form>
        <a href="{{ route('safe-git.repositories.diff', $repository) }}"><button type="button">diff</button></a>
        <a href="{{ route('safe-git.repositories.logs', $repository) }}"><button type="button">ログ</button></a>
    </div>
</div>

@if(isset($status['error']))
    <div class="alert error">{{ $status['error'] }}</div>
@endif

<div class="workbench">
    <aside class="panel pane">
        <div class="pane-header">
            <h2>ナビゲータ</h2>
            <span class="badge current">{{ $currentBranch }}</span>
        </div>
        <div class="pane-body">
            <div class="sidebar-section">
                <h3>Repository</h3>
                <p><strong>Default:</strong> {{ $repository->default_branch }}</p>
                @if($originRemote)
                    <p><span class="badge ok">origin 設定済み</span></p>
                    <p class="split-note">{{ $originUrl }}</p>
                @else
                    <p><span class="badge ng">origin 未設定</span></p>
                @endif
            </div>

            <div class="sidebar-section">
                <h3>Branches</h3>
                <div class="branch-list">
                    @forelse($branches as $branch)
                        <div class="branch-row">
                            <div class="branch-name">
                                @if($branch['current'])
                                    <span class="badge current">現在</span>
                                @elseif($branch['protected'])
                                    <span class="badge">保護</span>
                                @endif
                                <code>{{ $branch['name'] }}</code>
                            </div>
                            <div class="branch-actions">
                                @unless($branch['current'])
                                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.branches.switch', $repository) }}">
                                        @csrf
                                        <input type="hidden" name="branch" value="{{ $branch['name'] }}">
                                        <button type="submit" class="compact">切替</button>
                                    </form>
                                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.branches.delete', $repository) }}" onsubmit="return confirm('ローカルブランチ「{{ $branch['name'] }}」を削除します。よろしいですか？');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="branch" value="{{ $branch['name'] }}">
                                        @if($branch['protected'])
                                            <input type="hidden" name="confirm_delete_protected" value="1">
                                        @endif
                                        <button type="submit" class="compact danger">削除</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    @empty
                        <p class="muted">ブランチ情報がありません。</p>
                    @endforelse
                </div>
            </div>

            <div class="sidebar-section">
                <h3>Danger Zone</h3>
                <form method="POST" action="{{ route('safe-git.repositories.destroy', $repository) }}" onsubmit="return confirm('この管理画面から登録を削除します。ローカルフォルダとGitHubリポジトリは削除されません。よろしいですか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="danger">登録だけ削除</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="panel pane">
        <div class="pane-header">
            <h2>File Status</h2>
            <span class="badge">{{ count($files) }} changed</span>
        </div>
        <div class="pane-body">
            <div class="file-toolbar">
                <p class="muted">コミット対象の変更をステージングします。</p>
                <div class="actions">
                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
                        @csrf
                        <input type="hidden" name="path" value=".">
                        <button type="submit" class="primary">すべてステージング</button>
                    </form>
                    <a href="{{ route('safe-git.repositories.diff', $repository) }}">diffを見る</a>
                </div>
            </div>

            @if(!empty($status['conflicts']))
                <div class="alert error">
                    コンフリクトがあります:
                    {{ implode(', ', $status['conflicts']) }}
                </div>
            @endif

            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">状態</th>
                        <th>ファイル</th>
                        <th style="width: 170px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $file)
                        <tr>
                            <td><span class="badge">{{ $file['status'] }}</span></td>
                            <td class="file-name"><code>{{ $file['path'] }}</code></td>
                            <td>
                                <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
                                    @csrf
                                    <input type="hidden" name="path" value="{{ $file['path'] }}">
                                    <button type="submit" class="compact">このファイルを add</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">変更はありません。</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    <aside class="right-stack">
        <section class="panel pane">
            <div class="pane-header"><h2>Commit</h2></div>
            <div class="pane-body">
                <form method="POST" action="{{ route('safe-git.repositories.commit', $repository) }}">
                    @csrf
                    <label>コミットメッセージ</label>
                    <textarea name="message" rows="5" placeholder="例: UIをSourceTree風に調整" required></textarea>
                    <button type="submit" class="primary">commit</button>
                </form>
            </div>
        </section>

        <section class="panel pane">
            <div class="pane-header"><h2>Branch</h2></div>
            <div class="pane-body">
                <form method="POST" action="{{ route('safe-git.repositories.branches.create', $repository) }}">
                    @csrf
                    <label>新規ブランチ</label>
                    <input name="branch" placeholder="feature/function_add01" required>
                    <label><input type="checkbox" name="checkout" value="1" checked> 作成後に切り替える</label>
                    <button type="submit">ブランチ作成</button>
                </form>

                <form method="POST" action="{{ route('safe-git.repositories.branches.merge', $repository) }}">
                    @csrf
                    <label>現在ブランチへマージ</label>
                    <select name="branch" required>
                        @forelse($mergeBranches as $branch)
                            <option value="{{ $branch['name'] }}">{{ $branch['name'] }}</option>
                        @empty
                            <option value="">マージできるブランチがありません</option>
                        @endforelse
                    </select>
                    <button type="submit">merge</button>
                </form>
            </div>
        </section>

        <section class="panel pane">
            <div class="pane-header"><h2>Remote</h2></div>
            <div class="pane-body">
                <form method="POST" action="{{ route('safe-git.repositories.remote', $repository) }}">
                    @csrf
                    <label>Remote URL</label>
                    <input name="remote_url" value="{{ old('remote_url', $originUrl ?? $repository->remote_url) }}" placeholder="https://github.com/user/repo.git" required>
                    <label>Mode</label>
                    <select name="mode">
                        <option value="add" @selected($selectedRemoteMode === 'add')>remote add origin</option>
                        <option value="set-url" @selected($selectedRemoteMode === 'set-url')>remote set-url origin</option>
                    </select>
                    <button type="submit">Remote設定</button>
                </form>
            </div>
        </section>

        <section class="panel pane">
            <div class="pane-header"><h2>History</h2></div>
            <div class="pane-body">
                <pre>{{ $graph ?: 'ログはありません。' }}</pre>
            </div>
        </section>
    </aside>
</div>
@endsection

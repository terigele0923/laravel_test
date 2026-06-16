@extends('safe-git-manager.layout')

@section('content')
@php
    $originRemote = $status['remotes']['origin'] ?? null;
    $originUrl = $originRemote['push'] ?? $originRemote['fetch'] ?? null;
    $selectedRemoteMode = old('mode', $originRemote ? 'set-url' : 'add');
    $unstagedFiles = $status['unstaged_files'] ?? [];
    $stagedFiles = $status['staged_files'] ?? [];
    $branches = $status['branches'] ?? [];
    $remoteBranches = $status['remote_branches'] ?? [];
    $currentBranch = $status['branch'] ?? '-';
    $mergeBranches = array_values(array_filter($branches, fn ($branch) => ! $branch['current']));
    $history = $status['history'] ?? [];
    $stashes = $status['stashes'] ?? [];
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
            <label class="split-note"><input type="checkbox" name="confirm_dirty_pull" value="1"> 未コミットでもpull</label>
            <button type="submit">pull</button>
        </form>
        <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.push', $repository) }}">
            @csrf
            <label class="split-note"><input type="checkbox" name="confirm_main_push" value="1"> main/master確認</label>
            <button type="submit" class="danger">push</button>
        </form>
        <a href="{{ route('safe-git.repositories.logs', $repository) }}"><button type="button">操作ログ</button></a>
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
                <h3>Local Branches</h3>
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
                        <p class="muted">ローカルブランチがありません。</p>
                    @endforelse
                </div>
            </div>

            <div class="sidebar-section">
                <h3>Remote Branches</h3>
                <div class="branch-list">
                    @forelse($remoteBranches as $branch)
                        <div class="branch-row">
                            <div class="branch-name"><code>{{ $branch['name'] }}</code></div>
                            <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.branches.checkout-remote', $repository) }}">
                                @csrf
                                <input type="hidden" name="remote_branch" value="{{ $branch['name'] }}">
                                <input type="hidden" name="local_branch" value="{{ $branch['local_name'] }}">
                                <button type="submit" class="compact">checkout</button>
                            </form>
                        </div>
                    @empty
                        <p class="muted">リモートブランチがありません。fetch後に表示されます。</p>
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
            <span class="badge">{{ count($unstagedFiles) }} unstaged / {{ count($stagedFiles) }} staged</span>
        </div>
        <div class="pane-body">
            @if(!empty($status['conflicts']))
                <div class="alert error">
                    コンフリクトがあります: {{ implode(', ', $status['conflicts']) }}
                </div>
            @endif

            <div class="file-toolbar">
                <h2>Unstaged files</h2>
                <div class="actions">
                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
                        @csrf
                        <input type="hidden" name="path" value=".">
                        <button type="submit" class="primary">すべてステージング</button>
                    </form>
                    <a href="{{ route('safe-git.repositories.diff', $repository) }}">全体diff</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">状態</th>
                        <th>ファイル</th>
                        <th style="width: 270px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unstagedFiles as $file)
                        <tr>
                            <td><span class="badge">{{ $file['status'] }}</span></td>
                            <td class="file-name"><code>{{ $file['path'] }}</code></td>
                            <td>
                                <div class="actions">
                                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
                                        @csrf
                                        <input type="hidden" name="path" value="{{ $file['path'] }}">
                                        <button type="submit" class="compact">add</button>
                                    </form>
                                    <a href="{{ route('safe-git.repositories.diff', ['repository' => $repository, 'path' => $file['path']]) }}">diff</a>
                                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.discard', $repository) }}" onsubmit="return confirm('「{{ $file['path'] }}」の作業ツリー変更を破棄します。元に戻せません。よろしいですか？');">
                                        @csrf
                                        <input type="hidden" name="path" value="{{ $file['path'] }}">
                                        @if($file['untracked'])
                                            <input type="hidden" name="untracked" value="1">
                                        @endif
                                        <button type="submit" class="compact danger">破棄</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">未ステージの変更はありません。</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="file-toolbar" style="margin-top: 18px;">
                <h2>Staged files</h2>
                <span class="muted">commit に含まれるファイルです。</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">状態</th>
                        <th>ファイル</th>
                        <th style="width: 220px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stagedFiles as $file)
                        <tr>
                            <td><span class="badge ok">{{ $file['status'] }}</span></td>
                            <td class="file-name"><code>{{ $file['path'] }}</code></td>
                            <td>
                                <div class="actions">
                                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.unstage', $repository) }}">
                                        @csrf
                                        <input type="hidden" name="path" value="{{ $file['path'] }}">
                                        <button type="submit" class="compact">unstage</button>
                                    </form>
                                    <a href="{{ route('safe-git.repositories.diff', ['repository' => $repository, 'path' => $file['path'], 'cached' => 1]) }}">staged diff</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">ステージ済みファイルはありません。</td></tr>
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
                    <textarea name="message" rows="5" placeholder="例: UIとGit操作を改善" required></textarea>
                    <button type="submit" class="primary">commit staged</button>
                </form>
                <p class="split-note">ステージ済みファイルがない場合は commit を止めます。</p>
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
            <div class="pane-header"><h2>Stash</h2></div>
            <div class="pane-body">
                <form method="POST" action="{{ route('safe-git.repositories.stash.create', $repository) }}">
                    @csrf
                    <label>stash メッセージ</label>
                    <input name="message" placeholder="作業途中の退避">
                    <button type="submit">stash push</button>
                </form>

                <table>
                    <thead>
                        <tr><th>stash</th><th>操作</th></tr>
                    </thead>
                    <tbody>
                        @forelse($stashes as $stash)
                            <tr>
                                <td><code>{{ $stash['ref'] }}</code><br><span class="split-note">{{ $stash['message'] }}</span></td>
                                <td>
                                    <div class="actions">
                                        <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.stash.apply', $repository) }}">
                                            @csrf
                                            <input type="hidden" name="stash" value="{{ $stash['ref'] }}">
                                            <input type="hidden" name="mode" value="apply">
                                            <button type="submit" class="compact">apply</button>
                                        </form>
                                        <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.stash.apply', $repository) }}">
                                            @csrf
                                            <input type="hidden" name="stash" value="{{ $stash['ref'] }}">
                                            <input type="hidden" name="mode" value="pop">
                                            <button type="submit" class="compact">pop</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2">stash はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
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
                <table>
                    <thead>
                        <tr><th>Commit</th><th>Message</th></tr>
                    </thead>
                    <tbody>
                        @forelse($history as $commit)
                            <tr>
                                <td><code>{{ $commit['hash'] }}</code><br><span class="split-note">{{ $commit['date'] }} {{ $commit['author'] }}</span></td>
                                <td>{{ $commit['message'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">履歴はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </aside>
</div>
@endsection

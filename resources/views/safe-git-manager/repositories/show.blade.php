@extends('safe-git-manager.layout')

@section('content')
@php
    $isGitRepository = is_dir($repository->local_path.'/.git');
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
    $nextAction = count($unstagedFiles) > 0
        ? '未ステージの変更を add してください。'
        : (count($stagedFiles) > 0
            ? 'commit staged を実行できます。'
            : '変更はありません。必要なら fetch / pull で同期してください。');
@endphp

<div class="workbench-header">
    <div class="repo-title">
        <h1>{{ $repository->name }}</h1>
        <code>{{ $repository->local_path }}</code>
    </div>
    <div class="toolbar">
        <a class="button-link" href="{{ route('safe-git.repositories.logs', $repository) }}">操作ログ</a>
        <a class="button-link" href="{{ route('safe-git.repositories.index') }}">一覧へ戻る</a>
    </div>
</div>

<div class="summary-strip">
    <div class="summary-item">
        <span>現在のブランチ</span>
        <strong>{{ $currentBranch }}</strong>
    </div>
    <div class="summary-item">
        <span>Remote origin</span>
        <strong>{{ $originUrl ? '設定済み' : '未設定' }}</strong>
    </div>
    <div class="summary-item">
        <span>未ステージ</span>
        <strong>{{ count($unstagedFiles) }} files</strong>
    </div>
    <div class="summary-item">
        <span>ステージ済み</span>
        <strong>{{ count($stagedFiles) }} files</strong>
    </div>
    <div class="summary-item">
        <span>次の操作</span>
        <strong>{{ $nextAction }}</strong>
    </div>
</div>

@if(isset($status['error']))
    <div class="alert error">{{ $status['error'] }}</div>
@endif

@unless($isGitRepository)
    <section class="operation-area">
        <div class="area-header">
            <div class="area-heading"><span class="area-no">0</span><h2>初期設定</h2></div>
            <span class="badge ng">Git 未初期化</span>
        </div>
        <div class="area-body">
            <p class="area-description">このフォルダにはまだ <code>.git</code> がありません。最初に <code>git init</code> を実行してください。</p>
            <form class="actions" method="POST" action="{{ route('safe-git.repositories.init', $repository) }}">
                @csrf
                <label class="inline-check"><input type="checkbox" name="create_readme" value="1" checked> README を作成</label>
                <button type="submit" class="primary">git init</button>
            </form>
        </div>
    </section>
@endunless

<div class="area-grid">
    <main class="area-column">
        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">1</span><h2>作業の流れ</h2></div>
                <span class="badge">基本操作</span>
            </div>
            <div class="area-body">
                <div class="workflow-steps">
                    <div class="workflow-step">add<span>変更をステージへ移動</span></div>
                    <div class="workflow-step">commit<span>履歴として保存</span></div>
                    <div class="workflow-step">fetch / pull<span>GitHub と同期確認</span></div>
                    <div class="workflow-step">push<span>GitHub に反映</span></div>
                </div>
            </div>
        </section>

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">2</span><h2>変更ファイル管理</h2></div>
                <span class="badge">{{ count($unstagedFiles) }} unstaged / {{ count($stagedFiles) }} staged</span>
            </div>
            <div class="area-body">
                @if(!empty($status['conflicts']))
                    <div class="alert error">コンフリクトがあります: {{ implode(', ', $status['conflicts']) }}</div>
                @endif

                <section class="section-block">
                    <div class="file-toolbar">
                        <div>
                            <h2>Unstaged files</h2>
                            <p class="split-note">まだ commit 対象ではない変更です。必要なファイルを add してください。</p>
                        </div>
                        <div class="actions">
                            <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.add', $repository) }}">
                                @csrf
                                <input type="hidden" name="path" value=".">
                                <button type="submit" class="primary">すべて add</button>
                            </form>
                            <a class="button-link" href="{{ route('safe-git.repositories.diff', $repository) }}">全体 diff</a>
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
                                            <a class="button-link" href="{{ route('safe-git.repositories.diff', ['repository' => $repository, 'path' => $file['path']]) }}">diff</a>
                                            <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.discard', $repository) }}" onsubmit="return confirm('このファイルの作業ツリー変更を破棄します。元に戻せません。よろしいですか？');">
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
                </section>

                <section class="section-block">
                    <div class="file-toolbar">
                        <div>
                            <h2>Staged files</h2>
                            <p class="split-note">commit に含まれるファイルです。外したい場合は unstage してください。</p>
                        </div>
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
                                            <a class="button-link" href="{{ route('safe-git.repositories.diff', ['repository' => $repository, 'path' => $file['path'], 'cached' => 1]) }}">staged diff</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3">ステージ済みファイルはありません。</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>
        </section>

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">3</span><h2>コミット</h2></div>
                <span class="badge">{{ count($stagedFiles) }} staged</span>
            </div>
            <div class="area-body">
                <p class="area-description">ステージ済みファイルを履歴として保存します。メッセージは後から見返して分かる内容にしてください。</p>
                <form method="POST" action="{{ route('safe-git.repositories.commit', $repository) }}">
                    @csrf
                    <label>コミットメッセージ</label>
                    <textarea name="message" rows="4" placeholder="例: UI と Git 操作を改善" required></textarea>
                    <button type="submit" class="primary">commit staged</button>
                </form>
            </div>
        </section>

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">4</span><h2>GitHub 同期</h2></div>
                <span class="badge {{ $originUrl ? 'ok' : 'ng' }}">{{ $originUrl ? 'remote OK' : 'remote 未設定' }}</span>
            </div>
            <div class="area-body">
                <p class="area-description">GitHub の状態確認、取り込み、反映を行います。main/master への push は確認チェックが必要です。</p>
                <div class="actions">
                    <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.fetch', $repository) }}">
                        @csrf
                        <button type="submit">fetch</button>
                    </form>
                    <form class="mini-form actions" method="POST" action="{{ route('safe-git.repositories.pull', $repository) }}">
                        @csrf
                        <label class="inline-check"><input type="checkbox" name="confirm_dirty_pull" value="1"> 変更ありでも pull</label>
                        <button type="submit">pull</button>
                    </form>
                    <form class="mini-form actions" method="POST" action="{{ route('safe-git.repositories.push', $repository) }}">
                        @csrf
                        <label class="inline-check"><input type="checkbox" name="confirm_main_push" value="1"> main/master 確認済み</label>
                        <button type="submit" class="danger">push</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <aside class="area-column">
        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">5</span><h2>リポジトリ情報</h2></div>
                <span class="badge current">{{ $currentBranch }}</span>
            </div>
            <div class="area-body">
                <p><strong>Default branch:</strong> {{ $repository->default_branch }}</p>
                @if($originRemote)
                    <p><span class="badge ok">origin 設定済み</span></p>
                    <p class="split-note">{{ $originUrl }}</p>
                @else
                    <p><span class="badge ng">origin 未設定</span></p>
                    <p class="split-note">Remote 設定エリアで GitHub URL を登録してください。</p>
                @endif
            </div>
        </section>

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">6</span><h2>ブランチ操作</h2></div>
                <span class="badge">{{ count($branches) }} local</span>
            </div>
            <div class="area-body">
                <form method="POST" action="{{ route('safe-git.repositories.branches.create', $repository) }}">
                    @csrf
                    <label>新規ブランチ</label>
                    <input name="branch" placeholder="feature/function_add01" required>
                    <label class="inline-check"><input type="checkbox" name="checkout" value="1" checked> 作成後に切り替える</label>
                    <button type="submit">ブランチ作成</button>
                </form>

                <form method="POST" action="{{ route('safe-git.repositories.branches.merge', $repository) }}">
                    @csrf
                    <label>現在のブランチへマージ</label>
                    <select name="branch" required>
                        @forelse($mergeBranches as $branch)
                            <option value="{{ $branch['name'] }}">{{ $branch['name'] }}</option>
                        @empty
                            <option value="">マージできるブランチがありません</option>
                        @endforelse
                    </select>
                    <button type="submit">merge</button>
                </form>

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
                                        <form class="mini-form" method="POST" action="{{ route('safe-git.repositories.branches.delete', $repository) }}" onsubmit="return confirm('このローカルブランチを削除します。よろしいですか？');">
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
                            <div class="empty-state">ローカルブランチがありません。</div>
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
                            <div class="empty-state">fetch 後にリモートブランチが表示されます。</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">7</span><h2>Stash</h2></div>
                <span class="badge">{{ count($stashes) }} items</span>
            </div>
            <div class="area-body">
                <p class="area-description">作業中の変更を一時退避します。ブランチ切替や pull 前に便利です。</p>
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

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">8</span><h2>Remote 設定</h2></div>
                <span class="badge {{ $originUrl ? 'ok' : 'ng' }}">{{ $originUrl ? 'origin あり' : 'origin なし' }}</span>
            </div>
            <div class="area-body">
                <form method="POST" action="{{ route('safe-git.repositories.remote', $repository) }}">
                    @csrf
                    <label>Remote URL</label>
                    <input name="remote_url" value="{{ old('remote_url', $originUrl ?? $repository->remote_url) }}" placeholder="https://github.com/user/repo.git" required>
                    <label>Mode</label>
                    <select name="mode">
                        <option value="add" @selected($selectedRemoteMode === 'add')>remote add origin</option>
                        <option value="set-url" @selected($selectedRemoteMode === 'set-url')>remote set-url origin</option>
                    </select>
                    <button type="submit">Remote 設定</button>
                </form>
                <p class="split-note">origin が既にある場合は <code>set-url</code> が自動選択されます。</p>
            </div>
        </section>

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">9</span><h2>履歴</h2></div>
                <a class="button-link" href="{{ route('safe-git.repositories.logs', $repository) }}">操作ログ</a>
            </div>
            <div class="area-body">
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

        <section class="operation-area">
            <div class="area-header">
                <div class="area-heading"><span class="area-no">10</span><h2>管理</h2></div>
                <span class="badge ng">注意</span>
            </div>
            <div class="area-body">
                <p class="split-note">この画面からの登録だけを削除します。ローカルフォルダと GitHub リポジトリは削除しません。</p>
                <form method="POST" action="{{ route('safe-git.repositories.destroy', $repository) }}" onsubmit="return confirm('この画面から登録を削除します。ローカルフォルダと GitHub リポジトリは削除されません。よろしいですか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="danger">登録を削除</button>
                </form>
            </div>
        </section>
    </aside>
</div>
@endsection

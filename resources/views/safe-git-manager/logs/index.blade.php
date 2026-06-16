@extends('safe-git-manager.layout')

@section('content')
<div class="workbench-header">
    <div class="repo-title">
        <h1>操作ログ: {{ $repository->name }}</h1>
        <code>Git コマンドの実行履歴</code>
    </div>
    <a class="button-link" href="{{ route('safe-git.repositories.show', $repository) }}">操作画面へ戻る</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>日時</th>
                <th>操作</th>
                <th>状態</th>
                <th>コマンド</th>
                <th>出力</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->operation }}</td>
                    <td>
                        <span class="badge {{ $log->status === 'success' ? 'ok' : 'ng' }}">
                            {{ $log->status }}
                        </span>
                    </td>
                    <td><code>{{ $log->command }}</code></td>
                    <td>
                        <details>
                            <summary>表示</summary>
                            <pre>{{ $log->stdout }}{{ $log->stderr }}</pre>
                        </details>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">操作ログはありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $logs->links() }}
@endsection

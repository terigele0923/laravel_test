@extends('safe-git-manager.layout')

@section('content')
<h1>操作ログ: {{ $repository->name }}</h1>
<p><a href="{{ route('safe-git.repositories.show', $repository) }}">詳細へ戻る</a></p>

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

{{ $logs->links() }}
@endsection

@extends('safe-git-manager.layout')

@section('content')
<h1>操作ログ: {{ $repository->name }}</h1>
<p><a href="{{ route('safe-git.repositories.show', $repository) }}">戻る</a></p>

<table>
    <thead>
        <tr>
            <th>日時</th>
            <th>操作画面</th>
            <th>状態</th>
            <th>コマンド</th>
            <th>出力</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->operation }}</td>
                <td>{{ $log->status }}</td>
                <td><code>{{ $log->command }}</code></td>
                <td>
                    <details>
                        <summary>表示</summary>
                        <pre>{{ $log->stdout }}{{ $log->stderr }}</pre>
                    </details>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

{{ $logs->links() }}
@endsection

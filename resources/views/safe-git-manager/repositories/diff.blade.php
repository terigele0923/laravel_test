@extends('safe-git-manager.layout')

@section('content')
<div class="workbench-header">
    <div class="repo-title">
        <h1>Diff: {{ $repository->name }}</h1>
        <code>{{ $path ?: '全体 diff' }}</code>
    </div>
    <a class="button-link" href="{{ route('safe-git.repositories.show', $repository) }}">操作画面へ戻る</a>
</div>

<div class="card">
    @if(!empty($path))
        <p><strong>対象ファイル:</strong> <code>{{ $path }}</code></p>
        <p><strong>種類:</strong> {{ $cached ? 'ステージ済み diff' : '作業ツリー diff' }}</p>
    @else
        <p><strong>対象:</strong> 全体 diff</p>
    @endif
    <pre>{{ $result->stdout ?: $result->stderr ?: '差分はありません。' }}</pre>
</div>
@endsection

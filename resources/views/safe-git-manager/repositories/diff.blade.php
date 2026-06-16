@extends('safe-git-manager.layout')

@section('content')
<h1>Diff: {{ $repository->name }}</h1>
<p>
    <a href="{{ route('safe-git.repositories.show', $repository) }}">詳細へ戻る</a>
</p>

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

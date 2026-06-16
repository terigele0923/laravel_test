@extends('safe-git-manager.layout')

@section('content')
<h1>Diff: {{ $repository->name }}</h1>
<p><a href="{{ route('safe-git.repositories.show', $repository) }}">詳細へ戻る</a></p>

<div class="card">
    <pre>{{ $result->stdout ?: $result->stderr ?: '差分はありません。' }}</pre>
</div>
@endsection

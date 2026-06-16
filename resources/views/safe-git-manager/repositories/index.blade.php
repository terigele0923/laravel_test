@extends('safe-git-manager.layout')

@section('content')
<h1>リポジトリ一覧</h1>
<p><a href="{{ route('safe-git.repositories.create') }}">新規登録</a></p>

<table>
    <thead>
        <tr>
            <th>名前</th>
            <th>Local Path</th>
            <th>Remote</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        @foreach($repositories as $repository)
            <tr>
                <td>{{ $repository->name }}</td>
                <td>{{ $repository->local_path }}</td>
                <td>{{ $repository->remote_url }}</td>
                <td><a href="{{ route('safe-git.repositories.show', $repository) }}">詳細</a></td>
            </tr>
        @endforeach
    </tbody>
</table>

{{ $repositories->links() }}
@endsection

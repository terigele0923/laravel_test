@extends('safe-git-manager.layout')

@section('content')
<div class="actions" style="justify-content: space-between;">
    <div>
        <h1>リポジトリ一覧</h1>
        <p class="muted">この画面で管理するローカル Git プロジェクトを選びます。</p>
    </div>
    <a href="{{ route('safe-git.repositories.create') }}"><button class="primary">新規登録</button></a>
</div>

<table>
    <thead>
        <tr>
            <th>プロジェクト名</th>
            <th>ローカルパス</th>
            <th>登録済み Remote URL</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse($repositories as $repository)
            <tr>
                <td><strong>{{ $repository->name }}</strong></td>
                <td><code>{{ $repository->local_path }}</code></td>
                <td>{{ $repository->remote_url ?: '-' }}</td>
                <td>
                    <div class="actions">
                        <a href="{{ route('safe-git.repositories.show', $repository) }}">詳細を開く</a>
                        <form method="POST" action="{{ route('safe-git.repositories.destroy', $repository) }}" onsubmit="return confirm('この管理画面から登録を削除します。ローカルフォルダとGitHubリポジトリは削除されません。よろしいですか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger">登録削除</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">まだリポジトリが登録されていません。</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{ $repositories->links() }}
@endsection

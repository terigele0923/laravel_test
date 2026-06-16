@extends('safe-git-manager.layout')

@section('content')
<h1>リポジトリ登録</h1>
<p class="muted">管理したいローカルプロジェクトの場所を登録します。GitHub URL は後から設定できます。</p>

<div class="card">
    <form method="POST" action="{{ route('safe-git.repositories.store') }}">
        @csrf

        <label>プロジェクト名</label>
        <input name="name" value="{{ old('name') }}" placeholder="laravel_test" required>

        <label>ローカルパス</label>
        <input name="local_path" value="{{ old('local_path') }}" placeholder="C:\xampp\htdocs\laravel_test" required>

        <label>GitHub URL（後で設定可）</label>
        <input name="remote_url" value="{{ old('remote_url') }}" placeholder="https://github.com/user/repo.git">

        <label>デフォルトブランチ</label>
        <input name="default_branch" value="{{ old('default_branch', 'main') }}" required>

        <button type="submit" class="primary">登録</button>
    </form>
</div>
@endsection

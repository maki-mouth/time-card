@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/index.css') }}">
@endsection

@section('content')
<div class="staff-container">
    <h1 class="section-title">スタッフ一覧</h1>

    <div class="staff-card">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="name-column">{{ $user->name }}</td>
                    <td class="email-column">{{ $user->email }}</td>
                    <td class="detail-column">
                        {{-- 次のステップで作成する「スタッフ別勤怠一覧」へのリンク --}}
                        <a href="{{ route('admin.staff.attendance', ['id' => $user->id]) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
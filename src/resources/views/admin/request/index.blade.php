@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request/index.css') }}">
@endsection

@section('content')
<div class="request-container">
    <h1 class="page-title">申請一覧</h1>

    {{-- タブメニュー --}}
    <div class="tabs">
        <a href="{{ route('admin.request.index', ['status' => 'pending']) }}"
            class="tab-item {{ $status === 'pending' ? 'active' : '' }}">承認待ち</a>
        <a href="{{ route('admin.request.index', ['status' => 'approved']) }}"
            class="tab-item {{ $status === 'approved' ? 'active' : '' }}">承認済み</a>
    </div>

    {{-- 申請一覧テーブル --}}
    <div class="table-wrapper">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($corrections as $correction)
                <tr>
                    <td>{{ $correction->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                    <td>{{ $correction->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($correction->attendance->date)->format('Y/m/d') }}</td>
                    <td>{{ $correction->reason }}</td>
                    <td>{{ $correction->created_at->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('admin.request.index', ['attendance_correct_request_id' => $correction->id]) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
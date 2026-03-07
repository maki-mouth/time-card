@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request/approve.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h1 class="section-title">勤怠詳細</h1>

    <div class="detail-card">
        {{-- 名前 --}}
        <div class="detail-row">
            <label class="label">名前</label>
            <div class="content">
                <div class="name-display">{{ $correction->user->name }}</div>
            </div>
        </div>

        {{-- 日付 --}}
        <div class="detail-row">
            <label class="label">日付</label>
            <div class="content">
                <div class="date-display">
                    <span class="year">{{ \Carbon\Carbon::parse($correction->attendance->date)->format('Y年') }}</span>
                    <span class="day">{{ \Carbon\Carbon::parse($correction->attendance->date)->format('n月j日') }}</span>
                </div>
            </div>
        </div>

        {{-- 出勤・退勤 --}}
        <div class="detail-row">
            <label class="label">出勤・退勤</label>
            <div class="content">
                <div class="input-group">
                    <span>{{ $correction->requested_data['check_in'] }}</span>
                    <span class="separator">〜</span>
                    <span>{{ $correction->requested_data['check_out'] }}</span>
                </div>
            </div>
        </div>

        {{-- 休憩 --}}
        @php $breaks = $correction->requested_data['breaks'] ?? []; @endphp
        @for ($i = 0; $i < max(count($breaks), 1); $i++)
        <div class="detail-row">
            <label class="label">休憩{{ $i > 0 ? $i + 1 : '' }}</label>
            <div class="content">
                <div class="input-group">
                    @if(isset($breaks[$i]))
                        <span>{{ $breaks[$i]['start'] }}</span>
                        <span class="separator">〜</span>
                        <span>{{ $breaks[$i]['end'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endfor

        {{-- 備考 --}}
        <div class="detail-row no-border">
            <label class="label">備考</label>
            <div class="content">
                <div class="reason-display">{{ $correction->reason }}</div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.request.approve', $correction->id) }}" method="POST">
        @csrf
        <div class="form-actions">
            @if($correction->status === 'pending')
                {{-- まだ承認待ちの場合のみ、formと送信ボタンを表示 --}}
                <form action="{{ route('admin.request.approve', $correction->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-approve">承認</button>
                </form>
            @else
                {{-- 承認済みの場合は、添付画像の通りグレーのボタンを表示（クリック不可） --}}
                <button type="button" class="btn-approved" disabled>承認済み</button>
            @endif
        </div>
    </form>
</div>
@endsection
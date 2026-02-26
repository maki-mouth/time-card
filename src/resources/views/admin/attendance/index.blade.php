@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<main class="attendance-container">
    <h1 class="page-title">{{ \Carbon\Carbon::parse($today)->format('Y年n月j日') }}の勤怠</h1>
    {{-- 月選択ナビゲーション --}}
    <div class="month-nav">
        {{-- 前月へのリンク --}}
        <a href="{{ route('admin.attendance.index', ['date' => $prevDate]) }}" class="nav-arrow">← 前日</a>
        {{-- カレンダー選択部分 --}}
        <div class="month-picker-container">
            {{-- カレンダーアイコン --}}
            <label for="month-input" class="calendar-icon">
                <img src="{{ asset('img/calendar.png') }}" alt="calendar"> {{-- 画像があれば --}}
            </label>
                {{-- 表示テキスト --}}
                <span class="current-month">{{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}</span>

            <input type="date" id="date-input" class="month-hidden-input"
            value="{{ $date }}"
            onchange="location.href='{{ route('admin.attendance.index') }}?date=' + this.value">

        </div>
        {{-- 翌月へのリンク --}}
        <a href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}" class="nav-arrow">翌日 →</a>
    </div>
    
    <div class="table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $attendance)
                <tr>
                    {{-- Userモデルとのリレーションで名前を表示 --}}
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}</td>
                    <td>
                        {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}
                    </td>
                    <td>{{ $attendance->total_rest_time }}</td>
                    <td>{{ $attendance->total_work_time }}</td>
                    <td><a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>
@endsection
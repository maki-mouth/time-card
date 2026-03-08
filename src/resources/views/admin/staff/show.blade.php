@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff/show.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h1 class="page-title">{{ $user->name }}さんの勤怠</h1>

    {{-- 月選択ナビゲーション --}}
    <div class="month-nav">
        {{-- 前月へのリンク --}}
        <a href="{{ route('admin.staff.attendance', ['id' => $user->id, 'month' => $prevMonth]) }}" class="nav-arrow">← 前月</a>
        {{-- カレンダー選択部分 --}}
        <div class="month-picker-container">
            {{-- カレンダーアイコン --}}
            <label for="month-input" class="calendar-icon">
                <img src="{{ asset('img/calendar.png') }}" alt="calendar"> {{-- 画像があれば --}}
            </label>

            {{-- 表示テキスト --}}
            <span class="current-month">{{ \Carbon\Carbon::parse($month)->format('Y/m') }}</span>

            {{-- 実際のカレンダー入力（透明にして重ねるか、JSで制御） --}}
            <input type="month" id="month-input" class="month-hidden-input"
                value="{{ $month }}"
                onchange="location.href='{{ route('admin.staff.attendance', $user->id) }}?month=' + this.value">
        </div>
        {{-- 翌月へのリンク --}}
        <a href="{{ route('admin.staff.attendance', ['id' => $user->id, 'month' => $nextMonth]) }}" class="nav-arrow">翌月 →</a>
    </div>
    {{-- 勤怠テーブル --}}
    <div class="table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dates as $dateString => $data)
                    @php
                        $date = $data['date'];
                        $attendance = $data['attendance'];
                    @endphp
                    <tr>
                        {{-- 日付と曜日 --}}
                        <td>{{ $date->format('m/d') }}({{ $date->isoFormat('ddd') }})</td>

                        @if($attendance)
                            {{-- データがある場合 --}}
                            <td>{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}</td>
                            <td>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}</td>
                            <td>{{ $attendance->total_rest_time }}</td>
                            <td>{{ $attendance->total_work_time }}</td>
                            <td>
                                {{-- 既存データのIDを渡す --}}
                                <a href="{{ route('admin.attendance.show', ['id' => $attendance->id]) }}" class="detail-link">詳細</a>
                            </td>
                        @else
                            {{-- データがない日は空欄 --}}
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                {{-- 記録がない日は、日付パラメータを渡して「新規申請」扱いにさせる --}}
                                <a href="{{ route('admin.attendance.detail', ['id' => 'new', 'user_id' => $user->id, 'date' => $date->format('Y-m-d')]) }}" class="detail-link">詳細</a>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
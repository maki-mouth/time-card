@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h1 class="page-title">勤怠一覧</h1>

    {{-- 月選択ナビゲーション --}}
    <div class="month-nav">
        <a href="#" class="nav-arrow">← 前月</a>
        <span class="current-month">2023/06</span>
        <a href="#" class="nav-arrow">翌月 →</a>
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
                            <td>{{-- 休憩合計 --}}</td>
                            <td>{{-- 実働時間 --}}</td>
                            <td>
                                {{-- 既存データのIDを渡す --}}
                                <a href="{{ route('user.attendance.show', ['id' => $attendance->id]) }}" class="detail-link">詳細</a>
                            </td>
                        @else
                            {{-- データがない日は空欄 --}}
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                {{-- 記録がない日は、日付パラメータを渡して「新規申請」扱いにさせる --}}
                                <a href="{{ route('user.attendance.show', ['date' => $dateString]) }}" class="detail-link">詳細</a>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
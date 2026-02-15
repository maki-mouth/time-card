@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h2 class="page-title">勤怠一覧</h2>

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
                {{-- 本来は @foreach($attendances as $attendance) でループさせます --}}
                @for ($i = 1; $i <= 30; $i++)
                <tr>
                    <td>06/{{ sprintf('%02d', $i) }}(月)</td>
                    <td>09:00</td>
                    <td>18:00</td>
                    <td>1:00</td>
                    <td>8:00</td>
                    <td><a href="#" class="detail-link">詳細</a></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
@endsection
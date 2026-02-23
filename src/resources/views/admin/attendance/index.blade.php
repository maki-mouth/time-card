@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<main class="main">
    <h1 class="page-title">2023年6月1日の勤怠</h1>

    <div class="date-pager">
        <a href="#" class="date-pager__btn">&larr; 前日</a>
        <div class="date-pager__current">
            <span class="calendar-icon">📅</span>
            <span class="date-text">2023/06/01</span>
        </div>
        <a href="#" class="date-pager__btn">翌日 &rarr;</a>
    </div>

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
            {{-- 将来的に @foreach でループさせる部分 --}}
            <tr>
                <td>山田 太郎</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a href="#" class="detail-link">詳細</a></td>
            </tr>
            <tr>
                <td>西 伶奈</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a href="#" class="detail-link">詳細</a></td>
            </tr>
            <tr>
                <td>増田 一世</td>
                <td>09:00</td>
                <td>18:00</td>
                <td>1:00</td>
                <td>8:00</td>
                <td><a href="#" class="detail-link">詳細</a></td>
            </tr>
        </tbody>
    </table>
</main>
@endsection
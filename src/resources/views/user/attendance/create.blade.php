@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/create.css') }}">
@endsection

@section('content')
<div class="attendance-wrapper">
    <div class="attendance-container">
        {{-- 状態バッジ --}}
        <div class="status-group">
            @if(!$attendance || !$attendance->check_in)
                <span class="status-badge">勤務外</span>
            @elseif($attendance->check_out)
                <span class="status-badge">退勤済</span>
            @elseif($attendance->breakTimes()->whereNull('end_time')->exists())
                <span class="status-badge">休憩中</span>
            @else
                <span class="status-badge">出勤中</span>
            @endif
        </div>

        {{-- 日付と時刻 --}}
        <div class="datetime-display">
            <p class="date">{{ \Carbon\Carbon::now()->isoFormat('YYYY年M月D日(ddd)') }}</p>
            <p class="time" id="current-time">00:00</p> {{-- JSで動かすのが一般的です --}}
        </div>

        {{-- 打刻ボタンエリア --}}
        <div class="button-area">
            @if(!$attendance || !$attendance->check_in)
                {{-- 出勤前 --}}
                <form action="{{ route('user.attendance.punch') }}" method="POST">
                    @csrf
                    <button type="submit" name="type" value="check_in" class="btn btn-black">出勤</button>
                </form>

            @elseif(!$attendance->check_out)
                {{-- 出勤中（休憩中を含む） --}}
                @php $isBreaking = $attendance->breakTimes()->whereNull('end_time')->exists(); @endphp

                <div class="btn-group">
                    @if($isBreaking)
                        {{-- 休憩中 --}}
                        <form action="{{ route('user.attendance.punch') }}" method="POST">
                            @csrf
                            <button type="submit" name="type" value="break_end" class="btn btn-white">休憩戻</button>
                        </form>
                    @else
                        {{-- 通常勤務中 --}}
                        <form action="{{ route('user.attendance.punch') }}" method="POST">
                            @csrf
                            <button type="submit" name="type" value="check_out" class="btn btn-black">退勤</button>
                        </form>
                        <form action="{{ route('user.attendance.punch') }}" method="POST">
                            @csrf
                            <button type="submit" name="type" value="break_start" class="btn btn-white">休憩入</button>
                        </form>
                    @endif
                </div>

            @else
                {{-- 退勤後 --}}
                <p class="finish-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>

{{-- リアルタイムで時計を動かすための簡易スクリプト（お好みで） --}}
<script>
    function updateClock() {
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' +
                           now.getMinutes().toString().padStart(2, '0');
        document.getElementById('current-time').textContent = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
@endsection
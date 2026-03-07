@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h1 class="section-title">勤怠詳細</h1>

    <form action="" method="POST">
        @csrf
        <div class="detail-card">
            {{-- 名前 --}}
            <div class="detail-row">
                <label class="label">名前</label>
                <div class="content">
                    <div class="name-display">{{ $attendance->user->name }}</div>
                </div>
            </div>

            {{-- 日付 --}}
            <div class="detail-row">
                <label class="label">日付</label>
                <div class="content">
                    <div class="date-display">
                        <span class="year">{{ \Carbon\Carbon::parse($date)->format('Y年') }}</span>
                        <span class="day">{{ \Carbon\Carbon::parse($date)->format('n月j日') }}</span>
                    </div>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail-row">
                <label class="label">出勤・退勤</label>
                <div class="content">
                    <div class="input-group">
                        <input type="text" name="check_in" value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '') }}">
                        <span class="separator">〜</span>
                        <input type="text" name="check_out" value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '') }}">
                    </div>
                </div>
            </div>

        {{-- 休憩時間の表示ロジック --}}
        @php
            $breakCount = count($attendance->breakTimes);
            $displayCount = $breakCount + 1;
        @endphp

        @for ($i = 0; $i < $displayCount; $i++)
            <div class="detail-row">
                <label class="label">休憩{{ $i > 0 ? $i + 1 : '' }}</label>
                <div class="content">
                    <div class="input-group">
                        @php
                            $break = $attendance->breakTimes[$i] ?? null;
                        @endphp

                        @if($break)
                            <input type="hidden" name="breaks[{{ $i }}][id]" value="{{ $break->id }}">
                        @endif

                        <input type="text"
                            name="breaks[{{ $i }}][start]"
                            value="{{ $break ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }}">
                        <span class="separator">〜</span>
                        <input type="text" 
                            name="breaks[{{ $i }}][end]" 
                            value="{{ ($break && $break->end_time) ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}">
                    </div>
                </div>
            </div>
        @endfor

            {{-- 備考 --}}
            <div class="detail-row no-border">
                <label class="label">備考</label>
                <div class="content">
                    <textarea name="reason" rows="3">{{ old('reason', $attendance->correction->reason ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-update">修正</button>
        </div>
    </form>
</div>
@endsection
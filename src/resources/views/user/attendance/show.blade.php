@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <h1 class="section-title">勤怠詳細</h1>

    <form action="{{ route('user.attendance.store', $attendance->id) }}" method="POST">
        @csrf
        {{-- $idがない時のために日付を送る --}}
        @if(!$attendance->id)
            <input type="hidden" name="date" value="{{ $date }}">
        @endif
        <div class="detail-card">
            {{-- 名前 --}}
            <div class="detail-row">
                <label class="label">名前</label>
                <div class="content">
                    <div class="name-display">{{ auth()->user()->name }}</div>
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
                        @if($isPending)
                            {{-- 承認待ち：テキスト表示 --}}
                            <span>{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}</span>
                            <span class="separator">〜</span>
                            <span>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}</span>
                        @else
                            {{-- 通常時：入力フォーム --}}
                            <input type="text" name="check_in" value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '') }}">
                            <span class="separator">〜</span>
                            <input type="text" name="check_out" value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '') }}">
                        @endif
                    </div>
                </div>
            </div>

            {{-- 休憩時間 --}}
            @php
                $breakCount = count($attendance->breakTimes);
                $displayCount = $breakCount + 1;
            @endphp

            @for ($i = 0; $i < $displayCount; $i++)
                <div class="detail-row">
                    <label class="label">休憩{{ $i > 0 ? $i + 1 : '' }}</label>
                    <div class="content">
                        <div class="input-group">
                            @php $break = $attendance->breakTimes[$i] ?? null; @endphp
                            
                            @if($isPending)
                                {{-- 承認待ち：テキスト表示 --}}
                                <span>{{ $break ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }}</span>
                                <span class="separator">〜</span>
                                <span>{{ ($break && $break->end_time) ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}</span>
                            @else
                                {{-- 通常時：入力フォーム --}}
                                @if($break)
                                    <input type="hidden" name="breaks[{{ $i }}][id]" value="{{ $break->id }}">
                                @endif
                                <input type="text" name="breaks[{{ $i }}][start]" value="{{ $break ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }}">
                                <span class="separator">〜</span>
                                <input type="text" name="breaks[{{ $i }}][end]" value="{{ ($break && $break->end_time) ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}">
                            @endif
                        </div>
                    </div>
                </div>
            @endfor

            {{-- 備考 --}}
            <div class="detail-row no-border">
                <label class="label">備考</label>
                <div class="content">
                    @if($isPending)
                        {{-- 承認待ち：テキスト表示 --}}
                        <div class="note-display">{{ $attendance->corrections->first()->reason ?? '' }}</div>
                    @else
                        {{-- 通常時：テキストエリア --}}
                        <textarea name="reason" rows="3">{{ old('reason', $attendance->correction->reason ?? '') }}</textarea>
                    @endif
                </div>
            </div>
        </div>

        <div class="form-actions">
            @if($isPending)
                <p class="pending-message">*承認待ちのため修正はできません。</p>
            @else
                <button type="submit" class="btn-update">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection
<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('user.attendance.index');
    }

    public function create()
    {
        // 【重要】テスト中のみ、無理やりログイン状態にする
        if (!auth()->check()) {
            // ID 1のユーザー（Seederで作った一人目）としてログイン
            auth()->loginUsingId(1);
        }

        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 本日の勤怠レコードを取得（リレーションで休憩データも一緒に読み込む）
        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        return view('user.attendance.create', compact('attendance'));
    }

    /**
     * 打刻処理
     */
    public function punch(Request $request)
    {
        
    // 【重要】テスト中のみ、無理やりログイン状態にする
        if (!auth()->check()) {
            // ID 1のユーザー（Seederで作った一人目）としてログイン
            auth()->loginUsingId(1);
        }

        $user = Auth::user();
        $now = Carbon::now();
        $date = $now->toDateString();
        $type = $request->input('type');

        // 1. 本日の勤怠レコードを取得。なければ作成（出勤時など）
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $date]
        );

        // 2. 打刻タイプ（type）に応じて処理を分岐
        switch ($type) {
            case 'check_in':
                // 出勤：まだ出勤時刻が入っていない場合のみ更新
                if (!$attendance->check_in) {
                    $attendance->update(['check_in' => $now]);
                    $message = '出勤しました。';
                }
                break;

            case 'check_out':
                // 退勤：出勤済み かつ まだ退勤していない場合のみ更新
                if ($attendance->check_in && !$attendance->check_out) {
                    $attendance->update(['check_out' => $now]);
                    $message = '退勤しました。お疲れ様でした！';
                }
                break;

            case 'break_start':
                // 休憩入：出勤中 かつ 現在休憩中でない場合のみ作成
                $isBreaking = $attendance->breakTimes()->whereNull('end_time')->exists();
                if ($attendance->check_in && !$attendance->check_out && !$isBreaking) {
                    $attendance->breakTimes()->create(['start_time' => $now]);
                    $message = '休憩に入りました。';
                }
                break;

            case 'break_end':
                // 休憩戻：現在休憩中（end_timeがNULL）のレコードを探して更新
                $latestBreak = $attendance->breakTimes()->whereNull('end_time')->latest()->first();
                if ($latestBreak) {
                    $latestBreak->update(['end_time' => $now]);
                    $message = '休憩から戻りました。後半も頑張りましょう！';
                }
                break;

            default:
                return redirect()->back()->with('error', '不正な操作です。');
        }

        return redirect()->back()->with('success', $message ?? '打刻を記録しました。');
    }
}

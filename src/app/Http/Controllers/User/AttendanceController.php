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
    public function index(Request $request)
    {
        $user = auth()->user();

        // 表示したい月を取得（指定がなければ今月）
        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = \Carbon\Carbon::parse($month)->startOfMonth();
        $endOfMonth = \Carbon\Carbon::parse($month)->endOfMonth();

        // 1. その月の全日付を生成する
        $dates = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates[$date->toDateString()] = [
                'date' => $date->copy(),
                'attendance' => null, // 初期値はデータなし
            ];
        }

        // 2. DBからその月の打刻データを取得
        $attendances = \App\Models\Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy('date'); // 日付をキーにする

        // 3. 全日付データにDBのデータを流し込む
        foreach ($attendances as $date => $attendance) {
            if (isset($dates[$date])) {
                $dates[$date]['attendance'] = $attendance;
            }
        }

        return view('user.attendance.index', compact('dates', 'month'));
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

    public function show(Request $request, $id = null)
    {
        // IDがある場合：既存のデータを取得
        if ($id) {
            $attendance = Attendance::with('breakTimes')->findOrFail($id);
            $date = $attendance->date;
        }
        // IDがなく、日付パラメータがある場合：空のインスタンスを準備
        else {
            $date = $request->query('date');
            $attendance = new Attendance(['date' => $date]);
        }

        return view('user.attendance.show', compact('attendance', 'date'));
    }
}

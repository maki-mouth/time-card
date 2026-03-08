<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 今日が何日か取得（例：2023-06-01）
        $today = Carbon::today()->format('Y-m-d');

        // Attendancesテーブルから今日のデータを取り出す（User情報も一緒に取得）
        $attendances = Attendance::with('user')
            ->whereDate('date', $today)
            ->get();

        // URLのクエリパラメータ 'date' を取得。なければ今日の日付。
        $date = $request->query('date', \Carbon\Carbon::today()->format('Y-m-d'));

        // Carbonインスタンスに変換（前日・翌日の計算用）
        $currentDate = \Carbon\Carbon::parse($date);
        $prevDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        // 指定された日付の勤怠データを取得
        $attendances = Attendance::with('user')
            ->whereDate('date', $date)
            ->get();

        return view('admin.attendance.index', compact('today', 'attendances', 'date', 'prevDate', 'nextDate'));
    }

        public function show($id)
        {
            // 勤怠データをIDで取得（User情報も一緒に取得）
            $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);
            $date = $attendance->date;

            return view('admin.attendance.show', compact('attendance', 'date'));
        }

        /* 特定の日の勤怠詳細（新規・修正画面）を表示
     * ※AdminAttendanceControllerにこの役割がある場合は、そちらに書いてもOKです
     */
    public function editDay(Request $request, $id)
    {
        if ($id === 'new') {
            $attendance = new Attendance();
            $attendance->user_id = $request->query('user_id');

            // --- 修正箇所：URLの ?date=... から日付を取得 ---
            $dateString = $request->query('date');
            $attendance->date = $dateString;

            // ビューで使う $date 変数を用意（Carbonインスタンスにすると扱いやすいです）
            $date = \Carbon\Carbon::parse($dateString);

            $user = User::findOrFail($attendance->user_id);
        } else {
            $attendance = Attendance::with('user', 'breakTimes')->findOrFail($id);
            $user = $attendance->user;

            // 既存データの日付をセット
            $date = \Carbon\Carbon::parse($attendance->date);
        }

        // ビューに $date を追加して渡す
        return view('admin.attendance.show', compact('attendance', 'user', 'date'));
    }
}


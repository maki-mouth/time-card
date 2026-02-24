<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
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
}

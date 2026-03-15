<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧画面の表示
     */
    public function index()
    {
        // 一般ユーザー（role が user の人）のみを取得
        $users = User::where('role', 'user')->get();

        return view('admin.staff.index', compact('users'));
    }

    public function show(Request $request, $id)
    {
        // 1. 対象のスタッフを取得
        $user = User::findOrFail($id);
        
        // 1. 対象となる月を取得し、Carbonインスタンスを作成
        $month = $request->input('month', now()->format('Y-m'));
        $currentDate = \Carbon\Carbon::parse($month);

        // 2. 前月・翌月のリンク用文字列を作成
        $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');

        // 3. 月の開始日と終了日を定義
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        // 4. その月の全日付をキーにした配列を生成（初期値はnull）
        $dates = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates[$date->toDateString()] = [
                'date' => $date->copy(),
                'attendance' => null,
            ];
        }

        // 5. DBから該当月の打刻データを一括取得（Eager Load: breakTimes）
        $attendances = \App\Models\Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString()
            ])
            ->get()
            ->keyBy('date'); // 日付をキーにして検索しやすくする

        // 6. 生成した日付配列にDBのデータをマッピング
        foreach ($attendances as $date => $attendance) {
            if (isset($dates[$date])) {
                $dates[$date]['attendance'] = $attendance;
            }
        }

        // 7. ビューに必要な変数をすべて渡す
        return view('admin.staff.show', compact(
            'user',
            'dates',
            'month',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function export(Request $request, $user_id)
    {
        // 1. 対象スタッフの確認
        $user = User::findOrFail($user_id);
        
        // 2. 対象月の取得（デフォルトは当月）
        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        // 3. 勤怠データの取得
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'asc')
            ->get();

        // 4. CSV生成
        return new StreamedResponse(function () use ($user, $attendances, $month) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM付与（Excel文字化け対策）

            // ヘッダー（スタッフ名や対象月を先頭に入れると親切です）
            fputcsv($handle, ['スタッフ名', $user->name]);
            fputcsv($handle, ['対象月', $month]);
            fputcsv($handle, []); // 空行
            fputcsv($handle, ['日付', '出勤時間', '退勤時間', '休憩合計時間']);

            foreach ($attendances as $attendance) {
                // 休憩時間の合計（秒単位で集計してH:i形式にする例）
                $totalBreakSeconds = $attendance->breakTimes->sum(function($break) {
                    if ($break->start_time && $break->end_time) {
                        return Carbon::parse($break->start_time)->diffInSeconds(Carbon::parse($break->end_time));
                    }
                    return 0;
                });
                $breakTimeStr = gmdate('H:i', $totalBreakSeconds);

                fputcsv($handle, [
                    $attendance->date,
                    $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '',
                    $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '',
                    $breakTimeStr,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $user->name . '_' . $month . '.csv"',
        ]);
    }
}
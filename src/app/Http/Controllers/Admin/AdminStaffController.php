<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
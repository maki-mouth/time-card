<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Requests\AttendanceUpdateRequest;
use Illuminate\Support\Facades\DB;

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

            // --- ★ここを追加：showメソッドでもisPendingが必要 ---
            $isPending = $attendance->corrections()
                                    ->where('status', 'pending')
                                    ->exists();

            return view('admin.attendance.show', compact('attendance', 'date', 'isPending'));
        }

        /* 特定の日の勤怠詳細（新規・修正画面）を表示
     * ※AdminAttendanceControllerにこの役割がある場合は、そちらに書いてもOKです
     */
    public function editDay(Request $request, $id)
    {
        if ($id === 'new') {
            $attendance = new Attendance();
            $attendance->user_id = $request->query('user_id');

            $dateString = $request->query('date');
            $attendance->date = $dateString;
            $date = \Carbon\Carbon::parse($dateString);

            $user = User::findOrFail($attendance->user_id);
            
            // 新規作成時は、当然「承認待ちの申請」は存在しないので false
            $isPending = false;
        } else {
            $attendance = Attendance::with('user', 'breakTimes', 'corrections')->findOrFail($id);
            $user = $attendance->user;
            $date = \Carbon\Carbon::parse($attendance->date);

            // --- ★追加箇所：承認待ち(pending)の申請があるかチェック ---
            // Correctionsテーブルに status が pending のレコードがあるか確認します
            $isPending = $attendance->corrections()
                                    ->where('status', 'pending')
                                    ->exists();
        }

        // --- compact に 'isPending' を追加 ---
        return view('admin.attendance.show', compact('attendance', 'user', 'date', 'isPending'));
    }
    /* 勤怠データの新規作成・更新を処理
     */
    public function updateDay(AttendanceUpdateRequest $request, $id = null)
    {
        // 1. バリデーション済みデータを取得
        $validated = $request->validated();

        // 2. 既存データの取得（新規作成なら new）
        $attendance = Attendance::with('breakTimes')->findOrNew($id);

        // 新規作成時の処理（user_idやdateをセット）
        if (!$attendance->exists) {
            $attendance->user_id = $request->input('user_id');
            $attendance->date = $request->input('date');
        }

        // 3. 日付を取得（例: 2026-03-21）
        $date = $attendance->date;

        // 4. DB保存用に「日付 + 時間」の形式に整形（SQLエラー回避）
        $attendance->check_in = $date . ' ' . $validated['check_in'];
        $attendance->check_out = $date . ' ' . $validated['check_out'];
        $attendance->reason = $validated['reason'] ?? null;

        DB::transaction(function () use ($attendance, $validated) {
            // 5. 勤怠本体を保存
            $attendance->save();

            // 6. 休憩時間の更新（既存を一度消して、新しい内容で作り直すのが最も確実です）
            $attendance->breakTimes()->delete();

            if (isset($validated['breaks'])) {
                foreach ($validated['breaks'] as $break) {
                    if (!empty($break['start']) && !empty($break['end'])) {
                        $attendance->breakTimes()->create([
                            'start_time' => $attendance->date . ' ' . $break['start'],
                            'end_time'   => $attendance->date . ' ' . $break['end'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.attendance.detail', ['id' => $attendance->id]);
    }
}
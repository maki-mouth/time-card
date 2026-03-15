<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        
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
        return view('user.attendance.index', compact(
            'dates',
            'month',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function create()
    {
        // 【重要】テスト中のみ、無理やりログイン状態にする
        //if (!auth()->check()) {
            // ID 1のユーザー（Seederで作った一人目）としてログイン
            //auth()->loginUsingId(1);
        //}

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
        //if (!auth()->check()) {
            // ID 1のユーザー（Seederで作った一人目）としてログイン
            //auth()->loginUsingId(1);
        //}

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
        // IDがある場合：既存のデータを取得（承認待ち申請も一緒に読み込む）
        if ($id) {
            $attendance = Attendance::with(['breakTimes', 'corrections' => function($q) {
                $q->where('status', 'pending');
            }])->findOrFail($id);
            
            $date = $attendance->date;
            
            // 「statusがpendingの申請」が1件でもあれば true
            $isPending = $attendance->corrections->isNotEmpty();
        } 
        // IDがない（新規作成など）場合
        else {
            $date = $request->query('date');
            $attendance = new Attendance(['date' => $date]);
            $isPending = false; // 新規なので申請中はあり得ない
        }

        // compactに 'isPending' を追加してViewに渡す
        return view('user.attendance.show', compact('attendance', 'date', 'isPending'));
    }
/**
     * 修正申請の保存処理
     */
    public function store(AttendanceUpdateRequest $request, $id = null)
    {

        // 1. $idが渡されていない、もしくはDBに存在しない場合の処理
        $attendance = Attendance::with('breakTimes')->find($id);

        if (!$attendance) {
            // IDがない場合は新規作成（記録がない日の申請）
            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'date'    => $request->date, // hiddenで送られてくる日付
            ]);
            $id = $attendance->id;
        }

        // 2. 現在の勤怠データ（修正前）を取得
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // 3. オリジナルデータの作成（JSON用）
        $originalData = [
            'check_in' => $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : null,
            'check_out' => $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : null,
            'breaks' => $attendance->breakTimes->map(function($b) {
                return [
                    'start' => \Carbon\Carbon::parse($b->start_time)->format('H:i'),
                    'end' => $b->end_time ? \Carbon\Carbon::parse($b->end_time)->format('H:i') : null,
                ];
            })->toArray(),
        ];

        // 4. 申請データ（修正後）の作成（JSON用）
        $requestedData = [
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'breaks' => array_filter($request->breaks ?? [], function($b) {
                return !empty($b['start']); // 開始時間があるものだけ保存
            }),
        ];

        // 5. 保存実行
        DB::transaction(function () use ($attendance, $originalData, $requestedData, $request) {
            Correction::create([
                'user_id' => Auth::id(),
                'attendance_id' => $attendance->id,
                'status' => 'pending',
                'original_data' => $originalData,
                'requested_data' => $requestedData,
                'reason' => $request->reason,
            ]);
        });

        return redirect()->route('user.attendance.show', ['id' => $id]);
    }
}
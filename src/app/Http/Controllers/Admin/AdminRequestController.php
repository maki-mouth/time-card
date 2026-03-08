<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Correction;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



class AdminRequestController extends Controller
{
/**
     * 申請一覧画面の表示
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        // クエリパラメータからタブの状態を取得（承認待ち：pending / 承認済み：approved）
        $status = $request->query('status', 'pending');

        if ($user->role === 'admin') {
            // --- 👑 管理者の場合：全ユーザーの申請を取得 ---
            $corrections = \App\Models\Correction::where('status', $status)
                ->with(['user', 'attendance']) // リレーションをロード
                ->orderBy('created_at', 'desc')
                ->get();
                
            return view('admin.request.index', compact('corrections', 'status'));
            
        } else {
            // --- 👤 一般ユーザーの場合：自分の申請のみ取得 ---
            $corrections = \App\Models\Correction::where('user_id', $user->id)
                ->where('status', $status)
                ->with('attendance')
                ->orderBy('created_at', 'desc')
                ->get();
                
            return view('user.request.index', compact('corrections', 'status'));
        }
    }
    /* 申請詳細（承認画面）の表示
     */
    public function show($id)
    {
        // 申請データ、ユーザー、元の勤怠、休憩データを一括取得
        $correction = Correction::with(['user', 'attendance.breakTimes'])->findOrFail($id);
        
        return view('admin.request.approve', compact('correction'));
    }

    /**
     * 承認処理の実装
     */
    public function approve($id)
    {
        $correction = Correction::findOrFail($id);

        DB::transaction(function () use ($correction) {
            $attendance = $correction->attendance;
            $requested = $correction->requested_data;
            
            // 1. 対象の日付を取得（例: "2026-03-07"）
            $date = $attendance->date;

            // 2. 日付と申請時刻を結合して、正しいフォーマットにする
            // Carbonを使って "2026-03-07 12:59:00" のような形式を生成します
            $checkInDateTime = \Carbon\Carbon::parse($date . ' ' . $requested['check_in'])->toDateTimeString();
            $checkOutDateTime = $requested['check_out']
                ? \Carbon\Carbon::parse($date . ' ' . $requested['check_out'])->toDateTimeString()
                : null;

            // 3. 勤怠本体を更新
            $attendance->update([
                'check_in' => $checkInDateTime,
                'check_out' => $checkOutDateTime,
            ]);

            // --- 休憩時間の更新（ここは前回同様） ---
            $attendance->breakTimes()->delete();
            if (isset($requested['breaks'])) {
                foreach ($requested['breaks'] as $break) {
                    if (!empty($break['start'])) {
                        $attendance->breakTimes()->create([
                            // 休憩時間も同様に日付を付与
                            'start_time' => \Carbon\Carbon::parse($date . ' ' . $break['start'])->toDateTimeString(),
                            'end_time' => !empty($break['end']) 
                                ? \Carbon\Carbon::parse($date . ' ' . $break['end'])->toDateTimeString() 
                                : null,
                        ]);
                    }
                }
            }

            // 4. 申請ステータスを更新
            $correction->update(['status' => 'approved']);
        });

        return redirect()->back()->with('success', '承認しました');
    }
}

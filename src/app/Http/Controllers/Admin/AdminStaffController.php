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
    public function index()
    {
        $users = User::where('role', 'user')->get();

        return view('admin.staff.index', compact('users'));
    }

    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $month = $request->input('month', now()->format('Y-m'));
        $currentDate = \Carbon\Carbon::parse($month);

        $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        $dates = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates[$date->toDateString()] = [
                'date' => $date->copy(),
                'attendance' => null,
            ];
        }

        $attendances = \App\Models\Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString()
            ])
            ->get()
            ->keyBy('date');

        foreach ($attendances as $date => $attendance) {
            if (isset($dates[$date])) {
                $dates[$date]['attendance'] = $attendance;
            }
        }

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
        $user = User::findOrFail($user_id);

        $month = $request->input('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'asc')
            ->get();

        return new StreamedResponse(function () use ($user, $attendances, $month) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['スタッフ名', $user->name]);
            fputcsv($handle, ['対象月', $month]);
            fputcsv($handle, []);
            fputcsv($handle, ['日付', '出勤時間', '退勤時間', '休憩合計時間']);

            foreach ($attendances as $attendance) {
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
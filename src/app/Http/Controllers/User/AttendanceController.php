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

        return view('user.attendance.index', compact(
            'dates',
            'month',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        return view('user.attendance.create', compact('attendance'));
    }

    public function punch(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();
        $date = $now->toDateString();
        $type = $request->input('type');

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $date]
        );

        switch ($type) {
            case 'check_in':
                if (!$attendance->check_in) {
                    $attendance->update(['check_in' => $now]);
                }
                break;

            case 'check_out':
                if ($attendance->check_in && !$attendance->check_out) {
                    $attendance->update(['check_out' => $now]);
                }
                break;

            case 'break_start':
                $isBreaking = $attendance->breakTimes()->whereNull('end_time')->exists();
                if ($attendance->check_in && !$attendance->check_out && !$isBreaking) {
                    $attendance->breakTimes()->create(['start_time' => $now]);
                }
                break;

            case 'break_end':
                $latestBreak = $attendance->breakTimes()->whereNull('end_time')->latest()->first();
                if ($latestBreak) {
                    $latestBreak->update(['end_time' => $now]);
                }
                break;

            default:
                return redirect()->back();
        }

        return redirect()->back();
    }

    public function show(Request $request, $id = null)
    {
        if ($id) {
            $attendance = Attendance::with(['breakTimes', 'corrections' => function($q) {
                $q->where('status', 'pending');
            }])->findOrFail($id);

            $date = $attendance->date;

            $isPending = $attendance->corrections->isNotEmpty();
        } 
        else {
            $date = $request->query('date');
            $attendance = new Attendance(['date' => $date]);
            $isPending = false;
        }

        return view('user.attendance.show', compact('attendance', 'date', 'isPending'));
    }

    public function store(AttendanceUpdateRequest $request, $id = null)
    {

        $attendance = Attendance::with('breakTimes')->find($id);

        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'date'    => $request->date, // hiddenで送られてくる日付
            ]);
            $id = $attendance->id;
        }

        $attendance = Attendance::with('breakTimes')->findOrFail($id);

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

        $requestedData = [
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'breaks' => array_filter($request->breaks ?? [], function($b) {
                return !empty($b['start']);
            }),
        ];

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
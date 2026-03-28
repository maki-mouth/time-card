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
        $today = Carbon::today()->format('Y-m-d');

        $attendances = Attendance::with('user')
            ->whereDate('date', $today)
            ->get();

        $date = $request->query('date', \Carbon\Carbon::today()->format('Y-m-d'));

        $currentDate = \Carbon\Carbon::parse($date);
        $prevDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::with('user')
            ->whereDate('date', $date)
            ->get();

        return view('admin.attendance.index', compact('today', 'attendances', 'date', 'prevDate', 'nextDate'));
    }

        public function show($id)
        {
            $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);
            $date = $attendance->date;

            $isPending = $attendance->corrections()
                                    ->where('status', 'pending')
                                    ->exists();

            return view('admin.attendance.show', compact('attendance', 'date', 'isPending'));
        }


        public function editDay(Request $request, $id)
    {
        if ($id === 'new') {
            $attendance = new Attendance();
            $attendance->user_id = $request->query('user_id');

            $dateString = $request->query('date');
            $attendance->date = $dateString;
            $date = \Carbon\Carbon::parse($dateString);

            $user = User::findOrFail($attendance->user_id);

            $isPending = false;
        } else {
            $attendance = Attendance::with('user', 'breakTimes', 'corrections')->findOrFail($id);
            $user = $attendance->user;
            $date = \Carbon\Carbon::parse($attendance->date);

            $isPending = $attendance->corrections()
                                    ->where('status', 'pending')
                                    ->exists();
        }

        return view('admin.attendance.show', compact('attendance', 'user', 'date', 'isPending'));
    }

    public function updateDay(AttendanceUpdateRequest $request, $id = null)
    {
        $validated = $request->validated();

        $attendance = Attendance::with('breakTimes')->findOrNew($id);

        if (!$attendance->exists) {
            $attendance->user_id = $request->input('user_id');
            $attendance->date = $request->input('date');
        }

        $date = $attendance->date;

        $attendance->check_in = $date . ' ' . $validated['check_in'];
        $attendance->check_out = $date . ' ' . $validated['check_out'];
        $attendance->reason = $validated['reason'] ?? null;

        DB::transaction(function () use ($attendance, $validated) {
            $attendance->save();

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
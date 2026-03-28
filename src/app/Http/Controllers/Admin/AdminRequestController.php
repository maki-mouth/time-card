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
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status', 'pending');

        if ($user->role === 'admin') {
            $corrections = \App\Models\Correction::where('status', $status)
                ->with(['user', 'attendance'])
                ->orderBy('created_at', 'desc')
                ->get();

            return view('admin.request.index', compact('corrections', 'status'));

        } else {
            $corrections = \App\Models\Correction::where('user_id', $user->id)
                ->where('status', $status)
                ->with('attendance')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('user.request.index', compact('corrections', 'status'));
        }
    }

    public function show($id)
    {
        $correction = Correction::with(['user', 'attendance.breakTimes'])->findOrFail($id);

        return view('admin.request.approve', compact('correction'));
    }

    public function approve($id)
    {
        $correction = Correction::findOrFail($id);

        DB::transaction(function () use ($correction) {
            $attendance = $correction->attendance;
            $requested = $correction->requested_data;

            $date = $attendance->date;

            $checkInDateTime = \Carbon\Carbon::parse($date . ' ' . $requested['check_in'])->toDateTimeString();
            $checkOutDateTime = $requested['check_out']
                ? \Carbon\Carbon::parse($date . ' ' . $requested['check_out'])->toDateTimeString()
                : null;

            $attendance->update([
                'check_in' => $checkInDateTime,
                'check_out' => $checkOutDateTime,
            ]);

            $attendance->breakTimes()->delete();
            if (isset($requested['breaks'])) {
                foreach ($requested['breaks'] as $break) {
                    if (!empty($break['start'])) {
                        $attendance->breakTimes()->create([
                            'start_time' => \Carbon\Carbon::parse($date . ' ' . $break['start'])->toDateTimeString(),
                            'end_time' => !empty($break['end']) 
                                ? \Carbon\Carbon::parse($date . ' ' . $break['end'])->toDateTimeString() 
                                : null,
                        ]);
                    }
                }
            }

            $correction->update(['status' => 'approved']);
        });

        return redirect()->back()->with('success', '承認しました');
    }
}

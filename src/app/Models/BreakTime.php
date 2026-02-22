<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
    ];

    /**
     * 休憩時間に紐づく勤怠を取得
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}

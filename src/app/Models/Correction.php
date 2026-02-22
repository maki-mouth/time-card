<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Correction extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_id',
        'status',
        'requested_data',
        'reason',
    ];

    protected $casts = [
        'requested_data' => 'array', // JSONを配列として扱う
    ];

    /**
     * 修正申請に紐づく勤怠を取得
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}

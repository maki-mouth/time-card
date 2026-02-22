<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
    ];
    /**
     * 勤怠に紐づく休憩時間を取得
     */
    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * 勤怠に紐づく修正申請を取得
     */
    public function corrections()
    {
        return $this->hasMany(Correction::class);
    }
}

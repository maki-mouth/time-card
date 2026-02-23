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

    public function getTotalRestTimeAttribute()
    {
        $totalMinutes = 0;
        foreach ($this->breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $start = \Carbon\Carbon::parse($break->start_time);
                $end = \Carbon\Carbon::parse($break->end_time);
                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        // 分を H:i 形式に変換
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%01d:%02d', $hours, $minutes);
    }

    public function getTotalWorkTimeAttribute()
    {
        if (!$this->check_in || !$this->check_out) {
            return '';
        }

        $start = \Carbon\Carbon::parse($this->check_in);
        $end = \Carbon\Carbon::parse($this->check_out);

        // 滞在時間（分）
        $stayMinutes = $start->diffInMinutes($end);

        // 休憩時間（分）を再計算
        $restMinutes = 0;
        foreach ($this->breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $restMinutes += \Carbon\Carbon::parse($break->start_time)
                    ->diffInMinutes(\Carbon\Carbon::parse($break->end_time));
            }
        }

        // 実働時間 = 滞在時間 - 休憩時間
        $workMinutes = $stayMinutes - $restMinutes;

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;
        return sprintf('%01d:%02d', $hours, $minutes);
    }
}

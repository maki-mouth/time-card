<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
    ];

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

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

        $stayMinutes = $start->diffInMinutes($end);

        $restMinutes = 0;
        foreach ($this->breakTimes as $break) {
            if ($break->start_time && $break->end_time) {
                $restMinutes += \Carbon\Carbon::parse($break->start_time)
                    ->diffInMinutes(\Carbon\Carbon::parse($break->end_time));
            }
        }

        $workMinutes = $stayMinutes - $restMinutes;

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;
        return sprintf('%01d:%02d', $hours, $minutes);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

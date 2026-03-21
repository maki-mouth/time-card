<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(), // ユーザーも自動生成
            'date' => now()->format('Y-m-d'),
            'check_in' => now()->subHours(8), // 8時間前に出勤したことにする
            'check_out' => null,
        ];
    }
}
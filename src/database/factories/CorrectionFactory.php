<?php

namespace Database\Factories;

use App\Models\Correction;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionFactory extends Factory
{
    protected $model = Correction::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'attendance_id' => Attendance::factory(),
            'status' => 'pending',
            'reason' => '修正理由のテスト',
            // JSONカラムには空の配列をデフォルトとして設定
            'original_data' => json_encode([
                'check_in' => '09:00',
                'check_out' => '18:00',
            ]),
            'requested_data' => json_encode([
                'check_in' => '08:30',
                'check_out' => '17:30',
            ]),
        ];
    }
}
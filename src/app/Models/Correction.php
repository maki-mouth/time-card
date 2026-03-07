<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Correction extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_id',
        'status',
        'original_data',
        'requested_data',
        'reason',
    ];

    protected $casts = [
        'original_data' => 'array', // JSONを配列として扱う
        'requested_data' => 'array', // JSONを配列として扱う
    ];

    /**
     * この申請を紐付けたユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この申請の対象となる勤怠データを取得
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }}

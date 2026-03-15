<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 出勤・退勤
            'check_in' => ['required', 'date_format:H:i'],
            'check_out' => ['required', 'date_format:H:i', 'after:check_in'],

            // 休憩時間の配列バリデーション
            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i', 'after:check_in'], // 出勤後であること
            'breaks.*.end' => [
                'nullable',
                'date_format:H:i',
                'after:breaks.*.start', // 休憩開始より後であること
                'before:check_out'      // ★退勤時間より前であること
            ],

            // 備考
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            // 出勤・退勤に関するメッセージ
            'check_in.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_out.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            // 休憩時間そのものに関するメッセージ
            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.start.after' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.after' => '休憩時間が不適切な値です',

            // 休憩時間と退勤時間の矛盾に関するメッセージ
            'breaks.*.end.before' => '休憩時間もしくは退勤時間が不適切な値です',

            // 備考
            'reason.required' => '備考を記入してください',
        ];
    }
}
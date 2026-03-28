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
            'check_in' => ['required', 'date_format:H:i'],
            'check_out' => ['required', 'date_format:H:i', 'after:check_in'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'breaks.*.end' => [
                'nullable',
                'date_format:H:i',
                'after:breaks.*.start',
                'before:check_out'
            ],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_out.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.start.after' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.after' => '休憩時間が不適切な値です',
            'breaks.*.end.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'reason.required' => '備考を記入してください',
        ];
    }
}
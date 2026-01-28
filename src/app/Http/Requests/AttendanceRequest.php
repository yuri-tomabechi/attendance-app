<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
            'clock_in' => ['requires', 'date'],
            'clock_out' => ['required', 'date'],
            'break_start' => ['required', 'date'],
            'break_end' => ['required', 'date'],
            'reason' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn    = $this->clock_in;
            $clockOut   = $this->clock_out;
            $breakStart = $this->break_start;
            $breakEnd   = $this->break_end;


            if ($clockIn && $clockOut && $clockIn >= $clockOut) {
                $validator->errors()->add(
                    'clock_in',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            if (
                $breakStart &&
                ($breakStart < $clockIn || $breakStart > $clockOut)
            ) {
                $validator->errors()->add(
                    'break_start',
                    '休憩時間が不適切な値です'
                );
            }

            if ($breakEnd && $breakEnd > $clockOut) {
                $validator->errors()->add(
                    'break_end',
                    '休憩時間もしくは退勤時間が不適切な値です'
                );
            }
        });
    }
}

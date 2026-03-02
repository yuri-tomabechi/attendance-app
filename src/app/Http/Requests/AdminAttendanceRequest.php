<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceRequest extends FormRequest
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
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.break_start' => ['nullable', 'date_format:H:i'],
            'breaks.*.break_end'   => ['nullable', 'date_format:H:i'],
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


            if ($clockIn && $clockOut) {
                if ($clockIn >= $clockOut) {
                    $validator->errors()->add(
                        'clock_in',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            if ($this->breaks) {
                foreach ($this->breaks as $break) {

                    $breakStart = $break['break_start'] ?? null;
                    $breakEnd   = $break['break_end'] ?? null;

                    if ($clockIn && $clockOut && $breakStart) {
                        if ($breakStart < $clockIn || $breakStart > $clockOut) {
                            $validator->errors()->add(
                                'breaks',
                                '休憩時間が不適切な値です'
                            );
                        }
                    }

                    if ($breakStart && $breakEnd) {
                        if ($breakStart >= $breakEnd) {
                            $validator->errors()->add(
                                'breaks',
                                '休憩時間が不適切な値です'
                            );
                        }
                    }

                    if ($clockOut && $breakEnd) {
                        if ($breakEnd > $clockOut) {
                            $validator->errors()->add(
                                'break_end',
                                '休憩時間もしくは退勤時間が不適切な値です'
                            );
                        }
                    }
                }
            }
        });
    }
    public function messages()
    {
        return [
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'reason.required' => '備考を記入してください',
        ];
    }
}

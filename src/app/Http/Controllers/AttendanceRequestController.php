<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttendanceRequest;
use App\Models\AttendanceRequest as AttendanceRequestModel;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;

class AttendanceRequestController extends Controller
{
    public function show()
    {
        return view('user.attendance.detail');
    }

    public function store(AttendanceRequest $request)
    {
        DB::transaction(
            function () use ($request) {

            $attendance = Attendance::findOrFail($request->attendance_id);

            $attendanceRequest = AttendanceRequestModel::create([
                'attendance_id' => $attendance->id,
                'user_id' => auth()->id(),
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            if ($attendance->clock_in != $request->clock_in) {
                $attendanceRequest->items()->create([
                    'type'        => 'clock_in',
                    'target_id'   => $attendance->id,
                    'before_time' => $attendance->clock_in,
                    'after_time'  => $request->clock_in,
                ]);
            }

            if ($attendance->clock_out != $request->clock_out) {
                $attendanceRequest->items()->create(
                    [
                        'type'        => 'clock_out',
                        'target_id'   => $attendance->id,
                        'before_time' => $attendance->clock_out,
                        'after_time'  => $request->clock_out,
                    ]);
            }

            foreach ($request->breaks as $breakInput) {

                $break = BreakTime::findOrFail($breakInput['id']);

                // 休憩開始
                if ($break->break_start != $breakInput['break_start']) {
                    $attendanceRequest->items()->create([
                        'type'        => 'break_start',
                        'target_id'   => $break->id,
                        'before_time' => $break->break_start,
                        'after_time'  => $breakInput['break_start'],
                    ]);
                }

                // 休憩終了
                if ($break->break_end != $breakInput['break_end']) {
                    $attendanceRequest->items()->create([
                        'type'        => 'break_end',
                        'target_id'   => $break->id,
                        'before_time' => $break->break_end,
                        'after_time'  => $breakInput['break_end'],
                    ]);
                }
            }

            if ($attendanceRequest->items()->count() === 0) {
                throw new \Exception('変更内容なし');
            }
        });
        return back();
    }
}
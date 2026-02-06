<?php

namespace App\Http\Controllers;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->latest()
            ->first();

        return view('user.attendance.clock', compact('attendance'));
    }

    public function startWork()
    {
        Attendance::create([
            'user_id' => auth()->id(),
            'work_date'  => now()->toDateString(),
            'status' => 1, // 勤務中
            'clock_in' => now(),
        ]);

        return back();
    }

    public function startBreak()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('status', 1) // 勤務中のみ
            ->latest()
            ->firstOrFail();

        $attendance->update([
            'status' => 2, // 休憩中
        ]);

        return back();
    }

    public function endBreak()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('status', 2) // 休憩中のみ
            ->latest()
            ->firstOrFail();

        $attendance->update([
            'status' => 1, // 勤務中
        ]);

        return back();
    }

    public function endWork()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereIn('status', [1, 2])
            ->latest()
            ->firstOrFail();

        $attendance->update([
            'status'   => 0, // 勤務外
            'clock_out' => now(),
        ]);

        return back();
    }
}

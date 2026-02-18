<?php

namespace App\Http\Controllers;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\BreakTime;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', now()->toDateString())
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
            ->where('status', 1)
            ->latest()
            ->firstOrFail();

        // 休憩レコード作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        $attendance->update([
            'status' => 2,
        ]);

        return back();
    }


    public function endBreak()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('status', 2)
            ->latest()
            ->firstOrFail();

        $break = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest()
            ->first();

        if ($break) {
            $break->update([
                'break_end' => now(),
            ]);
        }

        $attendance->update([
            'status' => 1,
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

    public function list(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', auth()->id())
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy(function ($item) {
                return $item->work_date->format('Y-m-d');
            });

        return view('user.attendance.list', compact('month', 'start', 'end', 'attendances'));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['breaks', 'user', 'requests'])
            ->findOrFail($id);

        $pendingRequest = $attendance->requests()
            ->where('status', 'pending')
            ->exists();



        if ($attendance->user_id !== auth()->id()) {
            abort(403);
        }

        return view('user.attendance.detail', compact('attendance', 'pendingRequest'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Http\Requests\AttendanceRequest;
use App\Models\AttendanceRequest as AttendanceRequestModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::today();

        $users = User::where('role', 'user')->get();

        $attendances = Attendance::with('breaks')
            ->whereDate('work_date', $date)
            ->get()
            ->keyBy('user_id');

        return view('admin.attendance.index', compact('users', 'attendances', 'date'));
    }

    public function staff()
    {
        $users = User::where('role', 'user')->get();

        return view('admin.attendance.staff', compact('users'));
    }

    public function list(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $month = $request->month ?? now()->format('Y-m');
        $date = Carbon::parse($month);

        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy(function ($item) {
                return $item->work_date->format('Y-m-d');
            });

        return view('list', compact(
            'user',
            'month',
            'start',
            'end',
            'attendances'
        ));
    }

    public function update(AttendanceRequest $request, $id)
    {
        $request->validate([
            'clock_in'  => 'required',
            'clock_out' => 'required',
            'reason'    => 'required|string|max:255',
        ]);
        $attendance = Attendance::findOrFail($id);

        $attendance->update([
            'clock_in'  => $request->clock_in,
            'clock_out' => $request->clock_out,
        ]);

        $latestRequest = AttendanceRequestModel::where('attendance_id', $attendance->id)
            ->latest()
            ->first();

        if ($latestRequest) {
            $latestRequest->update([
                'status' => 'approved',
                'reason' => $request->reason,
            ]);
        } else {
            AttendanceRequestModel::create([
                'attendance_id' => $attendance->id,
                'user_id'       => $attendance->user_id,
                'status'        => 'approved',
                'reason'        => $request->reason,
            ]);
        }

        if ($request->breaks) {
            foreach ($request->breaks as $breakData) {
                $break = $attendance->breaks()
                    ->where('id', $breakData['id'])
                    ->first();

                if ($break) {
                    $break->update([
                        'break_start' => $breakData['break_start'],
                        'break_end'   => $breakData['break_end'],
                    ]);
                }
            }
        }


        return back()->with('success', '修正済み');
    }

    public function exportCsv(Request $request, User $user)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $month = $request->get('month', now()->format('Y-m'));

        $start = \Carbon\Carbon::parse($month)->startOfMonth();
        $end   = \Carbon\Carbon::parse($month)->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->get();

        $fileName = '勤怠一覧_' . $month . '_' . $user->name;

        return response()->streamDownload(function () use ($attendances) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                '日付',
                '出勤時間',
                '退勤時間',
                '勤務時間',
                '休憩時間'
            ]);

            foreach ($attendances as $attendance) {

                $totalBreakMinutes = 0;

                foreach ($attendance->breaks as $break) {
                    if ($break->break_start && $break->break_end) {
                        $totalBreakMinutes +=
                            \Carbon\Carbon::parse($break->break_start)
                            ->diffInMinutes($break->break_end);
                    }
                }

                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;

                $formattedBreak = sprintf('%02d:%02d', $breakHours, $breakMinutes);

                fputcsv($handle, [
                    \Carbon\Carbon::parse($attendance->work_date)->format('Y-m-d'),
                    $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $attendance->formatted_work_time ?? '',
                    $formattedBreak
                ]);
            }


            fclose($handle);
        }, $fileName . '.csv');
    }
}

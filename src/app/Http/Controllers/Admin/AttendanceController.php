<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
}

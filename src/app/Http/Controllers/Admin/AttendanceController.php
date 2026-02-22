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
}

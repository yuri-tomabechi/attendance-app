<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttendanceRequest;
use App\Models\AttendanceRequest as AttendanceRequestModel;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRequestController extends Controller
{
    // public function show()
    // {
    //     return view('user.attendance.detail');
    // }

    public function show($id)
    {
        $attendanceRequest = AttendanceRequestModel::with([
            'attendance.user',
            'attendance.breaks',
            'items'
        ])->findOrFail($id);

        return view('detail', [
            'attendance' => $attendanceRequest->attendance,
            'latestRequest' => $attendanceRequest,
            'isRequest' => true,
        ]);
    }

    public function store(AttendanceRequest $request)
    {
        $isAdmin = auth()->user()->role === 'admin';

        DB::transaction(
            function () use ($request, $isAdmin) {

                $attendance = Attendance::findOrFail($request->attendance_id);

                // 新しい値があればそれを使う、なければ既存値
                $finalClockIn = $request->clock_in
                    ? Carbon::parse($attendance->work_date)
                    ->setTimeFromTimeString($request->clock_in)
                    ->format('Y-m-d H:i:s')
                    : $attendance->clock_in;

                $finalClockOut = $request->clock_out
                    ? Carbon::parse($attendance->work_date)
                    ->setTimeFromTimeString($request->clock_out)
                    ->format('Y-m-d H:i:s')
                    : $attendance->clock_out;

                // 出勤・退勤整合性チェック
                // if ($finalClockIn >= $finalClockOut) {
                //     throw new \Exception('出勤時間と退勤時間の整合性が不正です');
                // }

                // 休憩チェック
                foreach ($request->breaks ?? [] as $breakInput) {

                    if (empty($breakInput['break_start']) && empty($breakInput['break_end'])) {
                        continue;
                    }

                    if (!empty($breakInput['id'])) {
                        $break = BreakTime::findOrFail($breakInput['id']);

                        $finalBreakStart = $breakInput['break_start']
                            ? Carbon::parse($attendance->work_date)
                            ->setTimeFromTimeString($breakInput['break_start'])
                            ->format('Y-m-d H:i:s')
                            : $break->break_start;

                        $finalBreakEnd = $breakInput['break_end']
                            ? Carbon::parse($attendance->work_date)
                            ->setTimeFromTimeString($breakInput['break_end'])
                            ->format('Y-m-d H:i:s')
                            : $break->break_end;
                    } else {
                        $finalBreakStart = Carbon::parse($attendance->work_date)
                            ->setTimeFromTimeString($breakInput['break_start']);

                        $finalBreakEnd = Carbon::parse($attendance->work_date)
                            ->setTimeFromTimeString($breakInput['break_end']);
                    }

                    if (
                        $finalBreakStart < $finalClockIn ||
                        $finalBreakEnd > $finalClockOut
                    ) {

                        throw new \Exception('休憩時間が勤務時間外です');
                    }

                    if ($finalBreakStart >= $finalBreakEnd) {
                        throw new \Exception('休憩時間の整合性が不正です');
                    }
                }


                $isAdmin = auth()->user()->role === 'admin';

                $attendanceRequest = AttendanceRequestModel::create([
                    'attendance_id' => $attendance->id,
                    'user_id' => auth()->id(),
                    'reason' => $request->reason,
                    'status' => $isAdmin ? 'approved' : 'pending',
                ]);

                if ($request->clock_in) {

                    $newClockIn = Carbon::parse($attendance->work_date)
                        ->setTimeFromTimeString($request->clock_in)
                        ->format('Y-m-d H:i:s');

                    if ($attendance->clock_in != $newClockIn) {
                        $attendanceRequest->items()->create([
                            'type'        => 'clock_in',
                            'target_id'   => $attendance->id,
                            'before_time' => $attendance->clock_in,
                            'after_time'  => $newClockIn,
                        ]);
                    }
                }

                if ($request->clock_out) {

                    $newClockOut = Carbon::parse($attendance->work_date)
                        ->setTimeFromTimeString($request->clock_out)
                        ->format('Y-m-d H:i:s');

                    if ($attendance->clock_out != $newClockOut) {
                        $attendanceRequest->items()->create([
                            'type'        => 'clock_out',
                            'target_id'   => $attendance->id,
                            'before_time' => $attendance->clock_out,
                            'after_time'  => $newClockOut,
                        ]);
                    }
                }

                if ($request->breaks) {
                    foreach ($request->breaks as $breakInput) {
                        if (empty($breakInput['break_start']) && empty($breakInput['break_end'])) {
                            continue;
                        }

                        if (!empty($breakInput['id'])) {
                            $break = BreakTime::findOrFail($breakInput['id']);

                            // 休憩開始
                            if ($breakInput['break_start']) {

                                $newBreakStart = Carbon::parse($attendance->work_date)
                                    ->setTimeFromTimeString($breakInput['break_start'])
                                    ->format('Y-m-d H:i:s');


                                if ($break->break_start != $newBreakStart) {
                                    $attendanceRequest->items()->create([
                                        'type'        => 'break_start',
                                        'target_id'   => $break->id,
                                        'before_time' => $break->break_start,
                                        'after_time'  => $newBreakStart,
                                    ]);
                                }
                            }

                            // 休憩終了
                            if ($breakInput['break_end']) {

                                $newBreakEnd = Carbon::parse($attendance->work_date)
                                    ->setTimeFromTimeString($breakInput['break_end'])
                                    ->format('Y-m-d H:i:s');

                                if ($break->break_end != $newBreakEnd) {
                                    $attendanceRequest->items()->create([
                                        'type'        => 'break_end',
                                        'target_id'   => $break->id,
                                        'before_time' => $break->break_end,
                                        'after_time'  => $newBreakEnd,
                                    ]);
                                }
                            }
                        } else {
                            $newBreakStart = Carbon::parse($attendance->work_date)
                                ->setTimeFromTimeString($breakInput['break_start'])->format('Y-m-d H:i:s');

                            $newBreakEnd = Carbon::parse($attendance->work_date)
                                ->setTimeFromTimeString($breakInput['break_end'])->format('Y-m-d H:i:s');

                            $attendanceRequest->items()->create([
                                'type' => 'new_break',
                                'target_id' => null,
                                'before_time' => null,
                                'after_time' => json_encode([
                                    'break_start' => $newBreakStart,
                                    'break_end' => $newBreakEnd
                                ]),
                            ]);
                        }
                    }
                    if ($attendanceRequest->items()->count() === 0) {
                        throw new \Exception('変更内容なし');
                    }

                }
                if ($isAdmin) {
                    foreach ($attendanceRequest->items as $item) {

                        if ($item->type === 'clock_in') {
                            Attendance::where('id', $item->target_id)
                                ->update(['clock_in' => $item->after_time]);
                        }

                        if ($item->type === 'clock_out') {
                            Attendance::where('id', $item->target_id)
                                ->update(['clock_out' => $item->after_time]);
                        }

                        if ($item->type === 'break_start') {
                            BreakTime::where('id', $item->target_id)
                                ->update(['break_start' => $item->after_time]);
                        }

                        if ($item->type === 'break_end') {
                            BreakTime::where('id', $item->target_id)
                                ->update(['break_end' => $item->after_time]);
                        }
                    }
                }
            }
        );
        return back();
    }

    public function index(Request $request)
    {
        $query = AttendanceRequestModel::with(['user', 'attendance']);

        if ($request->status === 'approved') {
            $query->where('status', 'approved');
        } else {
            $query->where('status', 'pending');
        }

        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->get();

        return view('requests.index', compact('requests'));
    }


    public function approve($id)
    {
        $attendanceRequest = AttendanceRequestModel::with('items')->findOrFail($id);

        DB::transaction(function () use ($attendanceRequest) {

            foreach ($attendanceRequest->items as $item) {

                if ($item->type === 'clock_in') {
                    Attendance::where('id', $item->target_id)
                        ->update(['clock_in' => $item->after_time]);
                }

                if ($item->type === 'clock_out') {
                    Attendance::where('id', $item->target_id)
                        ->update(['clock_out' => $item->after_time]);
                }

                if ($item->type === 'break_start') {
                    BreakTime::where('id', $item->target_id)
                        ->update(['break_start' => $item->after_time]);
                }

                if ($item->type === 'break_end') {
                    BreakTime::where('id', $item->target_id)
                        ->update(['break_end' => $item->after_time]);
                }

                if ($item->type === 'new_break') {

                    $data = json_decode($item->after_time, true);

                    BreakTime::create([
                        'attendance_id' => $attendanceRequest->attendance_id,
                        'break_start'   => $data['break_start'],
                        'break_end'     => $data['break_end'],
                    ]);
                }
            }

            $attendanceRequest->update([
                'status' => 'approved'
            ]);
        });

        return redirect()->route('attendance_requests.index');
    }
}

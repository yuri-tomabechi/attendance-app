<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {

            // 今月の20日分生成
            for ($i = 1; $i <= 20; $i++) {

                $date = now()->startOfMonth()->addDays($i);

                // 出勤時間 8:30〜10:00
                $clockIn = $date->copy()->setTime(rand(8, 9), rand(0, 59));

                // 退勤時間 17:00〜20:00
                $clockOut = $date->copy()->setTime(rand(17, 19), rand(0, 59));

                // 10%の確率で未退勤
                $isUnfinished = rand(1, 10) === 1;

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date,
                    'clock_in' => $clockIn,
                    'clock_out' => $isUnfinished ? null : $clockOut,
                    'status' => 0,
                ]);

                // 未退勤の日は休憩なし
                if ($isUnfinished) {
                    continue;
                }

                // 休憩回数 1〜2回
                $breakCount = rand(1, 2);

                for ($b = 0; $b < $breakCount; $b++) {

                    $startHour = 12 + ($b * 2);
                    $breakStart = $date->copy()->setTime($startHour, rand(0, 30));
                    $breakEnd = $breakStart->copy()->addMinutes(rand(30, 60));

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart,
                        'break_end' => $breakEnd,
                    ]);
                }
            }
        }
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;


class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤務外の場合ステータスが勤務外と表示される()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 27));

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('勤務外');
    }

    /** @test */
    public function 出勤中の場合ステータスが出勤中と表示される()
    {
        $now = Carbon::create(2026, 2, 27, 10, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'clock_out' => null,
            'status' => '1',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩中の場合ステータスが休憩中と表示される()
    {
        $now = Carbon::create(2026, 2, 27, 10, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'clock_out' => null,
            'status' => '2',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $now->copy()->addHour(),
            'break_end' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
    }

    /** @test */
    public function 退勤済の場合ステータスが退勤済と表示される()
    {
        $now = Carbon::create(2026, 2, 27, 10, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'clock_out' => $now->copy()->addHours(8),
            'status' => '0',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
    }
}

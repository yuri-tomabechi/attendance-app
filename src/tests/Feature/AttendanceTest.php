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


    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $this->actingAs($user);

        // ① 勤務外なので出勤ボタンが表示される
        $response = $this->get('/attendance');
        $response->assertSee('出勤');

        // ② 出勤処理
        $this->post('/attendance/start');

        // ③ DBに保存されているか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
        ]);

        // ④ ステータス確認
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 出勤は一日一回のみできる()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'clock_out' => $now->copy()->addHours(8),
            'status' => '4',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertDontSee('出勤');
    }

    /** @test */
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/attendance/start');

        $response = $this->get('/attendance/list');

        $response->assertSee($now->format('H:i'));
    }

    /** @test */
    public function 退勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();

        // 勤務中のデータを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'status' => 1, // 出勤中
            'clock_in' => now()->subHour(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        $this->post('/attendance/end');

        // DB確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 0,
        ]);
    }

    /** @test */
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // 出勤
        $this->post('/attendance/start');

        // 退勤
        $this->post('/attendance/end');

        $attendance = Attendance::where('user_id', $user->id)->first();

        $this->assertNotNull($attendance->clock_out);

        $response = $this->get('/attendance/list');

        $response->assertSee(
            \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')
        );
    }
}

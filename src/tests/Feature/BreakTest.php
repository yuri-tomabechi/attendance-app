<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 休憩ボタンが正しく機能する()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'clock_out' => null,
            'status' => '1', // 出勤中
        ]);

        $this->actingAs($user);

        // ① 休憩入ボタン表示確認
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        // ② 休憩入処理
        $this->post('/attendance/break/start');

        // ③ breakテーブル確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => null,
        ]);

        // ④ ステータス確認
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    /** @test */
    public function 休憩は一日に何回でもできる()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'status' => '1',
        ]);

        $this->actingAs($user);

        // 1回目休憩
        $this->post('/attendance/break/start');
        $this->post('/attendance/break/end');

        // 再度画面確認
        $response = $this->get('/attendance');

        $response->assertSee('休憩入');
    }

    /** @test */
    public function 休憩戻ボタンが正しく機能する()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'status' => '1',
        ]);

        $this->actingAs($user);

        $this->post('/attendance/break/start');
        $this->post('/attendance/break/end');

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩戻は一日に何回でもできる()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'status' => '1',
        ]);

        $this->actingAs($user);

        // 1回目
        $this->post('/attendance/break/start');
        $this->post('/attendance/break/end');

        // 2回目
        $this->post('/attendance/break/start');

        $response = $this->get('/attendance');

        $response->assertSee('休憩戻');
    }

    /** @test */
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        $now = Carbon::create(2026, 2, 27, 9, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now,
            'status' => '1',
        ]);

        $this->post('/attendance/break/start');

        Carbon::setTestNow($now->copy()->addMinutes(30));
        $this->post('/attendance/break/end');

        $response = $this->get('/attendance/list');

        $response->assertSee('09:00'); // 出勤
        $response->assertSee('00:30'); // 休憩合計時間
    }
}

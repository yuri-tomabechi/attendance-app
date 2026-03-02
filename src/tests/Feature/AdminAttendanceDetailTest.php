<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $admin = $this->admin();
        $user  = $this->user();

        $workDate = Carbon::parse('2026-03-03');

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $workDate,
            'clock_in'  => $workDate->copy()->setTime(9, 0, 0),
            'clock_out' => $workDate->copy()->setTime(18, 0, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start'   => $workDate->copy()->setTime(12, 0, 0),
            'break_end'     => $workDate->copy()->setTime(13, 0, 0),
        ]);

        $res = $this->actingAs($admin)->get(route('admin.attendance.show', $attendance->id));
        $res->assertStatus(200);

        // 名前・日付
        $res->assertSee($user->name);
        $res->assertSee($workDate->format('Y年'));
        $res->assertSee($workDate->format('n月j日'));

        // 時刻表示（Bladeで H:i 表示してる前提）
        $res->assertSee('09:00');
        $res->assertSee('18:00');
        $res->assertSee('12:00');
        $res->assertSee('13:00');
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合_エラーメッセージが表示される()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id'    => $user->id,
            'clock_in'   => '09:00:00',
            'clock_out'  => '18:00:00',
        ]);

        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'clock_in'  => '19:00',
            'clock_out' => '18:00',
            'reason'    => 'テスト備考',
            'breaks'    => [],
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合_エラーメッセージが表示される()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'clock_out' => '18:00:00',
        ]);

        // 管理者updateは breaks の中に id がある想定（既存休憩を更新するため）
        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start'   => '12:00:00',
            'break_end'     => '13:00:00',
        ]);

        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'id'          => $break->id,
                    'break_start' => '19:00',
                    'break_end'   => '19:30',
                ]
            ],
            'reason' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'breaks' => '休憩時間が不適切な値です'
        ]);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合_エラーメッセージが表示される()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'clock_out' => '18:00:00',
        ]);

        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start'   => '12:00:00',
            'break_end'     => '13:00:00',
        ]);

        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'id'          => $break->id,
                    'break_start' => '12:00',
                    'break_end'   => '21:00',
                ]
            ],
            'reason' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'break_end' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function 備考欄が未入力の場合_エラーメッセージが表示される()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'reason'    => '',
            'breaks'    => [],
        ]);

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください'
        ]);
    }
}

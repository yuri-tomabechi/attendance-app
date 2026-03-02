<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest as AttendanceRequestModel;
use App\Models\AttendanceRequestItem;

class AdminApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function 承認待ちの修正申請が全て表示されている()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $attendanceA = Attendance::factory()->create(['user_id' => $userA->id]);
        $attendanceB = Attendance::factory()->create(['user_id' => $userB->id]);

        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceA->id,
            'user_id'       => $userA->id,
            'status'        => 'pending',
            'reason'        => 'Aの未承認申請',
        ]);

        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceB->id,
            'user_id'       => $userB->id,
            'status'        => 'pending',
            'reason'        => 'Bの未承認申請',
        ]);

        $res = $this->get(route('admin.attendance_requests.index'));
        $res->assertStatus(200);

        $res->assertSee('Aの未承認申請');
        $res->assertSee('Bの未承認申請');
    }

    /** @test */
    public function 承認済みの修正申請が全て表示されている()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $attendanceA = Attendance::factory()->create(['user_id' => $userA->id]);
        $attendanceB = Attendance::factory()->create(['user_id' => $userB->id]);

        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceA->id,
            'user_id'       => $userA->id,
            'status'        => 'approved',
            'reason'        => 'Aの承認済み申請',
        ]);

        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceB->id,
            'user_id'       => $userB->id,
            'status'        => 'approved',
            'reason'        => 'Bの承認済み申請',
        ]);

        $res = $this->get(route('admin.attendance_requests.index', [
            'status' => 'approved'
        ]));
        $res->assertStatus(200);

        $res->assertSee('Aの承認済み申請');
        $res->assertSee('Bの承認済み申請');
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);
        $workDate = Carbon::parse('2026-03-03');

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $workDate,
            'clock_in'  => $workDate->copy()->setTime(9, 0, 0),
            'clock_out' => $workDate->copy()->setTime(18, 0, 0),
        ]);

        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start'   => $workDate->copy()->setTime(12, 0, 0),
            'break_end'     => $workDate->copy()->setTime(13, 0, 0),
        ]);

        $req = AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
            'reason'        => '詳細表示テスト',
        ]);

        AttendanceRequestItem::create([
            'attendance_request_id' => $req->id,
            'type'        => 'clock_in',
            'target_id'   => $attendance->id,
            'before_time' => $attendance->clock_in,
            'after_time'  => $workDate->copy()->setTime(10, 0, 0)->format('Y-m-d H:i:s'),
        ]);

        AttendanceRequestItem::create([
            'attendance_request_id' => $req->id,
            'type'        => 'break_end',
            'target_id'   => $break->id,
            'before_time' => $break->break_end,
            'after_time'  => $workDate->copy()->setTime(13, 30, 0)->format('Y-m-d H:i:s'),
        ]);

        $res = $this->get(route('attendance_requests.show', $req->id));
        $res->assertStatus(200);

        $res->assertSee('詳細表示テスト');
        $res->assertSee('10:00');
        $res->assertSee('13:30');
    }

    /** @test */
    public function 修正申請の承認処理が正しく行われる()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);
        $workDate = Carbon::parse('2026-03-03');

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $workDate,
            'clock_in'  => $workDate->copy()->setTime(9, 0, 0),
            'clock_out' => $workDate->copy()->setTime(18, 0, 0),
        ]);

        $break = BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start'   => $workDate->copy()->setTime(12, 0, 0),
            'break_end'     => $workDate->copy()->setTime(13, 0, 0),
        ]);

        $req = AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
            'reason'        => '承認テスト',
        ]);

        $newClockIn = $workDate->copy()->setTime(10, 0, 0)->format('Y-m-d H:i:s');
        $newBreakEnd = $workDate->copy()->setTime(13, 30, 0)->format('Y-m-d H:i:s');

        AttendanceRequestItem::create([
            'attendance_request_id' => $req->id,
            'type'        => 'clock_in',
            'target_id'   => $attendance->id,
            'before_time' => $attendance->clock_in,
            'after_time'  => $newClockIn,
        ]);

        AttendanceRequestItem::create([
            'attendance_request_id' => $req->id,
            'type'        => 'break_end',
            'target_id'   => $break->id,
            'before_time' => $break->break_end,
            'after_time'  => $newBreakEnd,
        ]);

        $res = $this->post(route('admin.attendance_requests.approve', $req->id));
        $res->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'id'       => $attendance->id,
            'clock_in' => $newClockIn,
        ]);

        $this->assertDatabaseHas('breaks', [
            'id'        => $break->id,
            'break_end' => $newBreakEnd,
        ]);

        $this->assertDatabaseHas('attendance_requests', [
            'id'     => $req->id,
            'status' => 'approved',
        ]);
    }
}

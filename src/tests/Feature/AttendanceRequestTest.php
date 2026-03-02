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

class AttendanceRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/attendance/request", [
            'attendance_id' => $attendance->id,
            'clock_in'  => '19:00',
            'clock_out' => '18:00',
            'reason'      => 'テスト備考',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/attendance/request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
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
    public function 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_out' => '18:00:00',
        ]);

        $response = $this->post("/attendance/request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
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
    public function 備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->post("/attendance/request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください'
        ]);
    }

    /** @test */
    public function 修正申請処理が実行される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->post("/attendance/request", [
            'attendance_id' => $attendance->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'reason' => '修正申請',
        ]);

        $this->assertDatabaseHas('attendance_requests', [
            'attendance_id' => $attendance->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function 「承認待ち」にログインユーザーが行った申請が全て表示されていること()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $attendanceA = Attendance::factory()->create([
            'user_id' => $userA->id,
        ]);

        $attendanceB = Attendance::factory()->create([
            'user_id' => $userB->id,
        ]);

        // Aの申請（pending）
        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceA->id,
            'user_id' => $userA->id,
            'status' => 'pending',
            'reason' => 'Aの申請',
        ]);

        // Bの申請（pending）
        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceB->id,
            'user_id' => $userB->id,
            'status' => 'pending',
            'reason' => 'Bの申請',
        ]);

        $this->actingAs($userA);

        $response = $this->get(route('attendance_requests.index'));

        $response->assertSee('Aの申請');
        $response->assertDontSee('Bの申請');
    }

    /** @test */
    public function 「承認済み」に管理者が承認した修正申請が全て表示されている()
    {
        $user = User::factory()->create([
            'role' => 'user', // 明示しておくと安全
        ]);

        $otherUser = User::factory()->create();

        $attendanceA = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $attendanceB = Attendance::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // 自分の承認済み
        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceA->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'reason' => '自分の承認済み申請',
        ]);

        // 他人の承認済み
        AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendanceB->id,
            'user_id' => $otherUser->id,
            'status' => 'approved',
            'reason' => '他人の承認済み申請',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('attendance_requests.index', [
            'status' => 'approved'
        ]));

        $response->assertStatus(200);

        // 自分のは見える
        $response->assertSee('自分の承認済み申請');

        // 他人のは見えない
        $response->assertDontSee('他人の承認済み申請');
    }

    /** @test */
    public function 各申請の「詳細」を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $request = AttendanceRequestModel::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            route('attendance_requests.show', $request->id)
        );

        $response->assertStatus(200);

        // 勤怠詳細画面が表示されていることを確認
        $response->assertViewIs('detail');
    }
}

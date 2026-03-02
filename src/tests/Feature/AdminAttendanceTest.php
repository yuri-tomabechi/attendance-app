<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;


class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $today = now()->format('Y-m-d');

        Attendance::factory()->create([
            'user_id' => $userA->id,
            'work_date' => $today,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $userB->id,
            'work_date' => $today,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.index'));

        $response->assertStatus(200);

        // 両方のユーザーの勤怠が見える
        $response->assertSee('09:00');
        $response->assertSee('10:00');
    }

    /** @test */
    public function 遷移した際に現在の日付が表示される()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $todayFormatted = now()->format('Y年n月j日');

        $response = $this->get(route('admin.attendance.index'));

        $response->assertSee($todayFormatted);
    }

    /** @test */
    public function 「前日」を押下した時に前の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $yesterday = now()->subDay()->format('Y-m-d');

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $yesterday,
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.index', [
            'date' => $yesterday
        ]));

        $response->assertSee('08:00');
    }

    /** @test */
    public function 「翌日」を押下した時に次の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $tomorrow = now()->addDay()->format('Y-m-d');

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $tomorrow,
            'clock_in' => '11:00:00',
            'clock_out' => '20:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.index', [
            'date' => $tomorrow
        ]));

        $response->assertSee('11:00');
    }
}

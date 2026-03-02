<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 自分が行った勤怠情報が全て表示されている()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user);

        // 自分の勤怠
        $myAttendances = collect();

        for ($i = 1; $i <= 3; $i++) {
            $myAttendances->push(
                Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => now()->startOfMonth()->addDays($i),
                ])
            );
        }

        // 他人の勤怠
        Attendance::factory()->count(2)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        foreach ($myAttendances as $attendance) {
            $formattedDate = Carbon::parse($attendance->work_date)
                ->locale('ja')
                ->isoFormat('MM/DD(ddd)');

            $response->assertSee($formattedDate);
        }
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(now()->format('Y/m'));
    }


    /** @test */
    public function 「前月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $previousMonth = now()->subMonth()->format('Y-m');

        $response = $this->get('/attendance/list?month=' . $previousMonth);

        $response->assertStatus(200);
        $response->assertSee(now()->subMonth()->format('Y/m'));
    }

    /** @test */
    public function 「翌月」を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $nextMonth = now()->addMonth()->format('Y-m');

        $response = $this->get('/attendance/list?month=' . $nextMonth);

        $response->assertStatus(200);
        $response->assertSee(now()->addMonth()->format('Y/m'));
    }

    /** @test */
    public function 「詳細」を押下すると、その日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
    }
}

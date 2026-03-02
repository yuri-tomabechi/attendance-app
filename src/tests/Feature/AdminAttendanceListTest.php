<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $users = User::factory()->count(3)->create([
            'role' => 'user',
        ]);

        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /** @test */
    public function 管理者が選択したユーザーの勤怠情報が正しく表示される()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $attendances = collect();
        for ($i = 1; $i <= 3; $i++) {
            $attendances->push(
                Attendance::factory()->create([
                    'user_id'   => $user->id,
                    'work_date' => now()->startOfMonth()->addDays($i),
                ])
            );
        }

        // 別ユーザーの勤怠（混ざらないこと確認用）
        $other = User::factory()->create(['role' => 'user']);
        Attendance::factory()->count(2)->create([
            'user_id' => $other->id,
        ]);

        $response = $this->get(route('admin.attendance.list', $user->id));
        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $formattedDate = Carbon::parse($attendance->work_date)
                ->locale('ja')
                ->isoFormat('MM/DD(ddd)');

            $response->assertSee($formattedDate);
        }
    }

    /** @test */
    public function 管理者が前月を押下した時に前月の情報が表示される()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $previousMonth = now()->subMonth()->format('Y-m');

        $response = $this->get(route('admin.attendance.list', [
            'userId' => $user->id,
            'month'  => $previousMonth,
        ]));

        $response->assertStatus(200);
        $response->assertSee(now()->subMonth()->format('Y/m'));
    }

    /** @test */
    public function 管理者が翌月を押下した時に翌月の情報が表示される()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $nextMonth = now()->addMonth()->format('Y-m');

        $response = $this->get(route('admin.attendance.list', [
            'userId' => $user->id,
            'month'  => $nextMonth,
        ]));

        $response->assertStatus(200);
        $response->assertSee(now()->addMonth()->format('Y/m'));
    }

    /** @test */
    public function 管理者が詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['role' => 'user']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('admin.attendance.show', $attendance->id));

        $response->assertStatus(200);
    }
}
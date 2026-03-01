<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use Tests\TestCase;
use Carbon\Carbon;

class ClockTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠画面に現在日時が表示される()
    {
        // ① 時間を固定
        $now = Carbon::setTestNow(Carbon::create(2026, 2, 27, 10, 30, 0));
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        // ② ログイン状態にする
        $this->actingAs($user);

        // ③ 画面にアクセス
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // ④ 日付検証（isoFormatと同じ形式で）
        $expectedDate = Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)');
        $expectedTime = Carbon::now()->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }

}

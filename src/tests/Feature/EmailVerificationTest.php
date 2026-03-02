<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 会員登録後認証メールが送信される()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '12341234',
            'password_confirmation' => '12341234',
        ]);

        $response->assertStatus(302);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function メール認証完了後勤怠登録画面に遷移する()
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $res = $this->get($verificationUrl);

        $res->assertStatus(302);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $attendancePage = $this->get('/attendance');
        $attendancePage->assertStatus(200);
    }
}

<?php

namespace App\Providers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // 会員登録画面
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン画面
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // Fortify::authenticateUsing(function (Request $request) {

        //     $user = User::where('email', $request->email)->first();

        //     if (
        //         $user &&
        //         Hash::check($request->password, $user->password)
        //     ) {
        //         return $user;
        //     }

        //     throw ValidationException::withMessages([
        //         'email' => 'ログイン情報が登録されていません',
        //     ]);
        // });

        // 独自の登録処理クラスを指定
        Fortify::createUsersUsing(CreateNewUser::class);

        // 登録成功後のリダイレクト先
        Fortify::verifyEmailView(function () {
            return view('auth.verify-notice');
        });
    }
}


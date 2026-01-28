<?php

namespace App\Providers;

use Illuminate\Http\Request;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

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
        Fortify::loginView(function (Request $request) {
            if ($request->is('admin/*')){
                return view('auth.admin-login');
            }
            return view('auth.login');
        });

        // 独自の登録処理クラスを指定
        Fortify::createUsersUsing(CreateNewUser::class);

        // 登録成功後のリダイレクト先
        // Fortify::verifyEmailView(function () {
        //     return view('auth.verify-notice');
        // });
    }
}

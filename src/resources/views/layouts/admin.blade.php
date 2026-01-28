<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>COATHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
   @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="/">
                <img src="{{ asset('images/logo.svg') }}" alt="">
            </a>
            <div class="header__right">
                <nav class="header__nav">
                    <ul>
                        <li class="all_attendance"><a href="">勤怠一覧</a></li>
                        <li class="all_staff"><a href="">スタッフ一覧</a></li>
                        <li><a href="all_request">申請一覧</a></li>
                        <li class="logout__button"><a href="/mylogout">ログアウト</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
    @yield('content')
    </main>

</body>
</html>
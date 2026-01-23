@extends('layouts.app')


@section('content')
<div class="form__content">
      <div class="form__heading">
        <h2>管理者ログイン</h2>
      </div>
      <form class="form" action="/admin/login" method="post" novalidate>
        @csrf
        <div class="form__group">
          <div class="form__group-title">
            <span class="form__label--item">メールアドレス</span>
          </div>
          <div class="form__group-content">
            <div class="form__input--text">
              <input type="email" name="email"  value="{{ old('email') }}"/>
            </div>
            <div class="form__error">
                @error('email')
                    {{ $message }} 
                @enderror
            </div>
          </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="password" name="password"  />
                </div>
                <div class="form__error">
                @error('password')
                {{ $message }}
                @enderror
                </div>
            </div>
        </div>
        <div class="form__button">
          <button class="form__button-submit" type="submit">ログインする</button>
        </div>
        <div class="login">
          <a href="{{ route('register')}}" class="button" >会員登録はこちら</a>
        </div>
      </form>
    </div>
@endsection
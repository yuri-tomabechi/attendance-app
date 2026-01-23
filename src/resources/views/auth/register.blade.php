@extends('layouts.app')

@section('content')
<div class="form__content">
      <div class="form__heading">
        <h2>会員登録</h2>
      </div>
      <form class="form" action="/register" method="post" novalidate>
        @csrf
        <div class="form__group">
          <div class="form__group-title">
            <span class="form__label--item">名前</span>
          </div>
          <div class="form__group-content">
            <div class="form__input--text name-inputs">
              <input type="text" name="name"  value="{{ old('name') }}" />
            </div>
            <div class="form__error">
                @error('name')
                    {{ $message }} 
                @enderror
            </div>
          </div>
        </div>
        <div class="form__group">
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
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード確認</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="password" name="password_confirmation" />
                </div>
                <div class="form__error">
                @error('password_confirmation')
                {{ $message }}
                @enderror
                </div>
            </div>
        </div>
        <div class="form__button">
          <button class="form__button-submit" type="submit">登録する</button>
        </div>
        <div class="login">
          <a href="{{ route('login')}}" class="button">ログインはこちら</a>
        </div>
      </form>
    </div>

@endsection
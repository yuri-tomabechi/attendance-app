@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}">
@endsection

@section('content')
    <div class="verify-notice">
            @if (session('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
            @endif
        <h2>登録していただいたメールアドレスに認証メールを送付しました。</h2>
        <p>メール認証を完了してください。</p>
        <button class="verify-button">認証はこちらから</button>
        <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn-resend">認証メールを再送する</button>
        </form>
    </div>
@endsection
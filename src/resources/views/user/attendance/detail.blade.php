@extends('layouts.user')

@section('meta')
    <meta http-equiv="refresh" content="10">
@endsection

@section('content')
<h2>あいうえお</h2>
<h2>{{ now()->format('Y/m/d H:i') }}</h2>

@endsection
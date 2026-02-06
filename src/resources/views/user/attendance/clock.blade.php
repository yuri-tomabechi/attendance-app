@extends('layouts.user')

@section('meta')
    <meta http-equiv="refresh" content="10">
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="clock-page">
    <div class="inner">
    <h1>
        @if(!$attendance || $attendance->status === 0)
            勤務外
        @elseif($attendance->status === 1)
            勤務中
        @elseif($attendance->status === 2)
            休憩中
        @endif
    </h1>
    <h2>{{ now()->isoFormat('YYYY年MM月DD日(ddd)') }}</h2>
    <h3>{{ now()->format('H:i') }}</h3>
    @if(!$attendance)
        <form method="POST" action="{{ route('attendance.start') }}">
        @csrf
            <button type="submit" class="clock-in-button">出勤</button>
        </form>
    @elseif($attendance->clock_out)
    {{-- 退勤済み --}}
        <p class="thanks">お疲れ様でした。</p>
    @elseif($attendance->status === 1)
      <div class="flex">
        <form method="POST" action="{{ route('attendance.end') }}">
        @csrf
            <button type="submit">退勤</button>
        </form>
        <form method="POST" action="{{ route('attendance.break.start') }}">
        @csrf
            <button type="submit" class="white">休憩入</button>
        </form>
      </div>
    @elseif($attendance->status === 2)
        <form method="POST" action="{{ route('attendance.break.end') }}">
        @csrf
            <button type="submit" class="white">休憩戻</button>
        </form>
    @endif
    </div>
</div>
@endsection
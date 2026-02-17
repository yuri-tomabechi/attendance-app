@extends('layouts.user')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
    <div class="detail-inner">
        @if ($errors->any())
            <div style="color:red;">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <h1 class="detail-title">勤怠詳細</h1>

        <form method="POST" action="{{ route('attendance.request.store', $attendance->id) }}">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

            <div class="detail-card">

                <div class="detail-row">
                    <span class="label">名前</span>
                    <span class="value">{{ $attendance->user->name }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">日付</span>
                    <span class="value">
                        {{ $attendance->work_date->format('Y年 n月j日') }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">出勤・退勤</span>
                    <span class="value">
                        <input type="time" name="clock_in" value="{{ $attendance->clock_in?->format('H:i') }}">
                        ～
                        <input type="time" name="clock_out" value="{{ $attendance->clock_out?->format('H:i') }}">
                    </span>
                </div>

                @foreach ($attendance->breaks as $index => $break)
                    <div class="detail-row">
                        <span class="label">休憩{{ $index + 1 }}</span>
                        <input type="time" name="breaks[{{ $index }}][break_start]"
                            value="{{ $break->break_start?->format('H:i') }}">

                        〜

                        <input type="time" name="breaks[{{ $index }}][break_end]"
                            value="{{ $break->break_end?->format('H:i') }}">
                        <input type="hidden" name="breaks[{{ $index }}][id]" value="{{ $break->id }}">
                    </div>
                @endforeach

                <div class="detail-row">
                    <span class="label">備考</span>
                    <textarea name="reason" class="remark"></textarea>
                </div>

            </div>

            <div class="detail-button">
                <button>修正</button>
            </div>
        </form>
    </div>
@endsection

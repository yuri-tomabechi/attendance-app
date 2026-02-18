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
                        @if (!$pendingRequest)
                            <input type="time" name="clock_in" value="{{ $attendance->clock_in?->format('H:i') }}">
                            ～
                            <input type="time" name="clock_out" value="{{ $attendance->clock_out?->format('H:i') }}">
                        @else
                            <span class="readonly">
                                {{ $attendance->clock_in?->format('H:i') ?? '--:--' }}
                                ～
                                {{ $attendance->clock_out?->format('H:i') ?? '--:--' }}
                            </span>
                        @endif
                    </span>
                </div>

                @foreach ($attendance->breaks as $index => $break)
                    <div class="detail-row">
                        <span class="label">休憩{{ $index + 1 }}</span>

                        @if (!$pendingRequest)
                            <input type="time" name="breaks[{{ $index }}][break_start]"
                                value="{{ $break->break_start?->format('H:i') }}">

                            〜

                            <input type="time" name="breaks[{{ $index }}][break_end]"
                                value="{{ $break->break_end?->format('H:i') }}">

                            <input type="hidden" name="breaks[{{ $index }}][id]" value="{{ $break->id }}">
                        @else
                            <span class="readonly">
                                {{ $break->break_start?->format('H:i') ?? '--:--' }}
                                〜
                                {{ $break->break_end?->format('H:i') ?? '--:--' }}
                            </span>
                        @endif
                    </div>
                @endforeach

                <div class="detail-row">
                    <span class="label">備考</span>
                    @if (!$pendingRequest)
                        <textarea name="reason" class="remark"></textarea>
                    @else
                        @php
                            $pending = $attendance->requests->where('status', 'pending')->first();
                        @endphp

                        <div class="readonly-remark">
                            {{ $pending->reason }}
                        </div>
                    @endif
                </div>

            </div>

            <div class="detail-button">
                @if (!$pendingRequest)
                    <button type="submit">修正</button>
                @else
                    <p class="pending-message">
                        ※承認待ちのため申請はできません。
                    </p>
                @endif
            </div>
        </form>
    </div>
@endsection

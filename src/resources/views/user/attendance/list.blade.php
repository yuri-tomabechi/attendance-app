@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="list-inner">
    <h1 class="list-title">勤怠一覧</h1>

    <div class="month-nav">
        <a href="{{ route('attendance.list', ['month' => \Carbon\Carbon::parse($month)->subMonth()->format('Y-m')]) }}">
            ← 前月
        </a>
        <div class="calender-flex">
            <img src="{{ asset('images/calender.svg') }}" alt="" class="calender">
            <span class="month-title">{{ \Carbon\Carbon::parse($month)->format('Y/m') }}</span>
        </div>
        <a href="{{ route('attendance.list', ['month' => \Carbon\Carbon::parse($month)->addMonth()->format('Y-m')]) }}">
            翌月 →
        </a>
    </div>

    <table class="list-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @for ($date = $start->copy(); $date <= $end; $date->addDay())
                @php
                    $attendance = $attendances[$date->toDateString()] ?? null;
                @endphp

                <tr>
                    <td>{{ $date->isoFormat('MM/DD(ddd)') }}</td>

                    <td>{{ optional($attendance)->clock_in?->format('H:i') ?? '-' }}</td>

                    <td>{{ optional($attendance)->clock_out?->format('H:i') ?? '-' }}</td>

                    <td>
                        @if($attendance)
                            {{ $attendance->formatted_break_time ?? '00:00' }}
                        @else
                            -
                        @endif
                    </td>

                    <td>
                        @if($attendance && $attendance->clock_out)
                            {{ $attendance->formatted_work_time ?? '00:00' }}
                        @else
                            -
                        @endif
                    </td>

                    <td>
                        @if($attendance)
                            <a href="{{ route('attendance.show', $attendance->id) }}">詳細</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
@endsection

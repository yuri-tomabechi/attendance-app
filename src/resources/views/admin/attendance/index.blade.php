@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endsection

@section('content')
    <div class="admin-inner">
        <h1 class="admin-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>
        <div class="date-nav">
            <a href="{{ route('admin.attendance.index', ['date' => $date->copy()->subDay()->toDateString()]) }}">
                ← 前日
            </a>
            <a href="{{ route('admin.attendance.index', ['date' => $date->copy()->addDay()->toDateString()]) }}">
                翌日 →
            </a>
        </div>

        <table class="attendance-card">
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>

            @foreach ($users as $user)
                @php
                    $attendance = $attendances[$user->id] ?? null;

                    $clockIn = $attendance?->clock_in;
                    $clockOut = $attendance?->clock_out;

                    $breakMinutes = $attendance
                        ? $attendance->breaks->sum(function ($break) {
                            if ($break->break_start && $break->break_end) {
                                return \Carbon\Carbon::parse($break->break_start)->diffInMinutes($break->break_end);
                            }
                            return 0;
                        })
                        : 0;

                    $workMinutes =
                        $clockIn && $clockOut
                            ? \Carbon\Carbon::parse($clockIn)->diffInMinutes($clockOut) - $breakMinutes
                            : 0;
                @endphp

                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $clockIn ? \Carbon\Carbon::parse($clockIn)->format('H:i') : '--:--' }}</td>
                    <td>{{ $clockOut ? \Carbon\Carbon::parse($clockOut)->format('H:i') : '--:--' }}</td>
                    <td>{{ gmdate('H:i', $breakMinutes * 60) }}</td>
                    <td>{{ gmdate('H:i', $workMinutes * 60) }}</td>
                    <td>
                        <a href="#">詳細</a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection

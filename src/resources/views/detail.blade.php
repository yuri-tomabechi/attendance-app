@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="detail-inner">
    <h1 class="detail-title">勤怠詳細</h1>

    {{-- ============================= --}}
    {{-- 🔵 管理者画面 --}}
    {{-- ============================= --}}
    @if(auth()->user()->role === 'admin')

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
                <span class="readonly">
                    {{ $pendingRequest && $pendingRequest->items->where('type','clock_in')->first()
                        ? \Carbon\Carbon::parse($pendingRequest->items->where('type','clock_in')->first()->after_time)->format('H:i')
                        : optional($attendance->clock_in)->format('H:i') }}

                    ～

                    {{ $pendingRequest && $pendingRequest->items->where('type','clock_out')->first()
                        ? \Carbon\Carbon::parse($pendingRequest->items->where('type','clock_out')->first()->after_time)->format('H:i')
                        : optional($attendance->clock_out)->format('H:i') }}
                </span>
            </div>

            @foreach ($attendance->breaks as $index => $break)
                @php
                    $pendingStart = $pendingRequest?->items
                        ->where('type', 'break_start')
                        ->where('target_id', $break->id)
                        ->first();

                    $pendingEnd = $pendingRequest?->items
                        ->where('type', 'break_end')
                        ->where('target_id', $break->id)
                        ->first();
                @endphp

                <div class="detail-row">
                    <span class="label">休憩{{ $index + 1 }}</span>
                    <span class="readonly">
                        {{ $pendingStart
                            ? \Carbon\Carbon::parse($pendingStart->after_time)->format('H:i')
                            : optional($break->break_start)->format('H:i') }}

                        ～

                        {{ $pendingEnd
                            ? \Carbon\Carbon::parse($pendingEnd->after_time)->format('H:i')
                            : optional($break->break_end)->format('H:i') }}
                    </span>
                </div>
            @endforeach

            <div class="detail-row">
                <span class="label">備考</span>
                <div class="readonly-remark">
                    {{ $pendingRequest->reason ?? '' }}
                </div>
            </div>

        </div>

        {{-- 承認ボタン --}}
        @if(isset($pendingRequest) && $pendingRequest->status === 'pending')
            <form method="POST"
                  action="{{ route('attendance_requests.approve', $pendingRequest->id) }}">
                @csrf
                <div class="detail-button">
                    <button type="submit" class="approve-btn">
                        承認する
                    </button>
                </div>
            </form>
        @endif


    {{-- ============================= --}}
    {{-- 🟢 一般ユーザー画面 --}}
    {{-- ============================= --}}
    @else

        <form method="POST" action="{{ route('attendance.request.store') }}">
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

                    @if (!$pendingRequest)
                        <input type="time" name="clock_in"
                            value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}">
                        ～
                        <input type="time" name="clock_out"
                            value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}">
                    @else
                        <span class="readonly">
                            {{ optional($attendance->clock_in)->format('H:i') }}
                            ～
                            {{ optional($attendance->clock_out)->format('H:i') }}
                        </span>
                    @endif
                </div>

                @foreach ($attendance->breaks as $index => $break)
                    <div class="detail-row">
                        <span class="label">休憩{{ $index + 1 }}</span>

                        @if (!$pendingRequest)
                            <input type="time"
                                name="breaks[{{ $index }}][break_start]"
                                value="{{ old("breaks.$index.break_start", optional($break->break_start)->format('H:i')) }}">

                            ～

                            <input type="time"
                                name="breaks[{{ $index }}][break_end]"
                                value="{{ old("breaks.$index.break_end", optional($break->break_end)->format('H:i')) }}">

                            <input type="hidden"
                                name="breaks[{{ $index }}][id]"
                                value="{{ $break->id }}">
                        @else
                            <span class="readonly">
                                {{ optional($break->break_start)->format('H:i') }}
                                ～
                                {{ optional($break->break_end)->format('H:i') }}
                            </span>
                        @endif
                    </div>
                @endforeach

                <div class="detail-row">
                    <span class="label">備考</span>

                    @if (!$pendingRequest)
                        <textarea name="reason" class="remark">{{ old('reason') }}</textarea>
                    @else
                        <div class="readonly-remark">
                            {{ $pendingRequest->reason }}
                        </div>
                    @endif
                </div>

                @if ($errors->any())
                    <div style="color:red;">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

            </div>

            @if (!$pendingRequest)
                <div class="detail-button">
                    <button type="submit">修正</button>
                </div>
            @else
                <p class="pending-message">
                    ※承認待ちのため申請はできません。
                </p>
            @endif

        </form>

    @endif

</div>
@endsection

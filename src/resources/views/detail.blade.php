@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
    <div class="detail-inner">
        <h1 class="detail-title">勤怠詳細</h1>

        {{-- ============================= --}}
        {{-- 管理者画面 --}}
        {{-- ============================= --}}
        @if (auth()->user()->role === 'admin')

            <div class="detail-card">

                <div class="detail-row">
                    <span class="label">名前</span>
                    <span class="value">{{ $attendance->user->name }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">日付</span>
                    <div class="value flex">
                        <span>{{ $attendance->work_date->format('Y年') }}</span>
                        <span>{{ $attendance->work_date->format('n月j日') }}</span>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="label">出勤・退勤</span>
                    @if (!$latestRequest)
                        {{-- 申請なし → 管理者が直接修正 --}}
                        <div class="flex">
                            <input type="time" name="clock_in"
                                value="{{ optional($attendance->clock_in)->format('H:i') }}">
                            <span class="wave">～</span>
                            <input type="time" name="clock_out"
                                value="{{ optional($attendance->clock_out)->format('H:i') }}">
                        </div>
                    @else
                        <span class="readonly flex">
                            {{ $latestRequest && $latestRequest->items->where('type', 'clock_in')->first()
                                ? \Carbon\Carbon::parse($latestRequest->items->where('type', 'clock_in')->first()->after_time)->format('H:i')
                                : optional($attendance->clock_in)->format('H:i') }}

                            <span class="wave">～</span>

                            {{ $latestRequest && $latestRequest->items->where('type', 'clock_out')->first()
                                ? \Carbon\Carbon::parse($latestRequest->items->where('type', 'clock_out')->first()->after_time)->format('H:i')
                                : optional($attendance->clock_out)->format('H:i') }}
                        </span>
                    @endif
                </div>

                @foreach ($attendance->breaks as $index => $break)
                    @php
                        $pendingStart = $latestRequest?->items
                            ->where('type', 'break_start')
                            ->where('target_id', $break->id)
                            ->first();

                        $pendingEnd = $latestRequest?->items
                            ->where('type', 'break_end')
                            ->where('target_id', $break->id)
                            ->first();
                    @endphp

                    <div class="detail-row">
                        <span class="label">休憩{{ $index + 1 }}</span>

                        @if (!$latestRequest)
                            {{-- 申請なし → 管理者が直接修正 --}}
                            <div class="flex">
                                <input type="time" name="breaks[{{ $index }}][break_start]"
                                    value="{{ optional($break->break_start)->format('H:i') }}">

                                <span class="wave">～</span>

                                <input type="time" name="breaks[{{ $index }}][break_end]"
                                    value="{{ optional($break->break_end)->format('H:i') }}">

                                <input type="hidden" name="breaks[{{ $index }}][id]" value="{{ $break->id }}">
                            </div>
                        @else
                            {{-- 申請あり --}}
                            <span class="readonly flex">
                                {{ $pendingStart
                                    ? \Carbon\Carbon::parse($pendingStart->after_time)->format('H:i')
                                    : optional($break->break_start)->format('H:i') }}
                                <span class="wave">～</span>
                                {{ $pendingEnd
                                    ? \Carbon\Carbon::parse($pendingEnd->after_time)->format('H:i')
                                    : optional($break->break_end)->format('H:i') }}
                            </span>
                        @endif
                    </div>
                @endforeach
                @php
                    $nextIndex = $attendance->breaks->count();
                    $newBreakItem = null;

                    if ($latestRequest) {
                        $newBreakItem = $latestRequest->items->where('type', 'new_break')->first();
                    }
                @endphp

                <div class="detail-row">
                    <span class="label">休憩{{ $nextIndex + 1 }}</span>
                    @if (!$latestRequest)
                        <div class="flex">
                            <input type="time" name="breaks[{{ $nextIndex }}][break_start]">
                            <span class="wave">～</span>
                            <input type="time" name="breaks[{{ $nextIndex }}][break_end]">
                        </div>
                    @else
                        <span class="readonly flex">
                            @if ($newBreakItem)
                                @php
                                    $data = json_decode($newBreakItem->after_time, true);
                                @endphp
                                    {{ \Carbon\Carbon::parse($data['break_start'])->format('H:i') }}
                                    <span class="wave">～</span>
                                    {{ \Carbon\Carbon::parse($data['break_end'])->format('H:i') }}
                            @else
                            @endif
                        </span>
                    @endif
                </div>

                <div class="detail-row">
                    <span class="label">備考</span>

                    @if (!$latestRequest)
                        <textarea name="reason" class="remark"></textarea>
                    @else
                        <div class="readonly-remark">
                            {{ $latestRequest->reason }}
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
            @if ($isRequest)
                {{-- 申請詳細画面 --}}
                @if ($latestRequest->status === 'pending')
                    <form method="POST" action="{{ route('attendance_requests.approve', $latestRequest->id) }}">
                        @csrf
                        <div class="detail-button">
                            <button type="submit" class="approve-btn">
                                承認する
                            </button>
                        </div>
                    </form>
                @else
                    <div class="detail-button">
                        <span class="approved-message">承認済み</span>
                    </div>
                @endif
            @else
                @if ($latestRequest && $latestRequest->status === 'pending')
                    <div class="detail-button">
                        <span class="pending-message">
                            ※承認待ちのため修正はできません。
                        </span>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}">
                        @csrf
                        <div class="detail-button">
                            <button type="submit">修正</button>
                        </div>
                    </form>
                @endif
            @endif


            {{-- ============================= --}}
            {{-- 一般ユーザー画面 --}}
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
                        <div class="value flex">
                        <span>{{ $attendance->work_date->format('Y年') }}</span>
                        <span>{{ $attendance->work_date->format('n月j日') }}</span>
                    </div>
                    </div>

                    <div class="detail-row">
                        <span class="label">出勤・退勤</span>

                        @if (!$latestRequest)
                        <div class="flex">
                            <input type="time" name="clock_in"
                                value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}">
                            <div class="wave">～</div>
                            <input type="time" name="clock_out"
                                value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}">
                                </div>
                        @else
                            @php
                                $pendingClockIn = $latestRequest?->items->where('type', 'clock_in')->first();
                                $pendingClockOut = $latestRequest?->items->where('type', 'clock_out')->first();
                            @endphp

                            <span class="readonly flex">
                                {{ $pendingClockIn
                                    ? \Carbon\Carbon::parse($pendingClockIn->after_time)->format('H:i')
                                    : optional($attendance->clock_in)->format('H:i') }}

                                <div class="wave">～</div>

                                {{ $pendingClockOut
                                    ? \Carbon\Carbon::parse($pendingClockOut->after_time)->format('H:i')
                                    : optional($attendance->clock_out)->format('H:i') }}
                            </span>
                        @endif
                    </div>

                    @foreach ($attendance->breaks as $index => $break)
                        <div class="detail-row">
                            <span class="label">休憩{{ $index + 1 }}</span>

                            @if (!$latestRequest)
                            <div class="flex">
                                <input type="time" name="breaks[{{ $index }}][break_start]"
                                    value="{{ old("breaks.$index.break_start", optional($break->break_start)->format('H:i')) }}">

                                <div class="wave">～</div>

                                <input type="time" name="breaks[{{ $index }}][break_end]"
                                    value="{{ old("breaks.$index.break_end", optional($break->break_end)->format('H:i')) }}">

                                <input type="hidden" name="breaks[{{ $index }}][id]" value="{{ $break->id }}">
                                </div>
                            @else
                                @php
                                    $pendingStart = $latestRequest?->items
                                        ->where('type', 'break_start')
                                        ->where('target_id', $break->id)
                                        ->first();

                                    $pendingEnd = $latestRequest?->items
                                        ->where('type', 'break_end')
                                        ->where('target_id', $break->id)
                                        ->first();
                                @endphp

                                <span class="readonly flex">
                                    {{ $pendingStart
                                        ? \Carbon\Carbon::parse($pendingStart->after_time)->format('H:i')
                                        : optional($break->break_start)->format('H:i') }}

                                    <div class="wave">～</div>

                                    {{ $pendingEnd
                                        ? \Carbon\Carbon::parse($pendingEnd->after_time)->format('H:i')
                                        : optional($break->break_end)->format('H:i') }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                    @php
                        $nextIndex = $attendance->breaks->count();
                        $newBreakItem = $latestRequest?->items->where('type', 'new_break')->first();
                    @endphp

                    <div class="detail-row">
                        <span class="label">休憩{{ $nextIndex + 1 }}</span>

                        @if (!$latestRequest)
                        <div class="flex">
                            <input type="time" name="breaks[{{ $nextIndex }}][break_start]">
                            <div class="wave">～</div>
                            <input type="time" name="breaks[{{ $nextIndex }}][break_end]">
                            </div>
                        @else
                            <span class="readonly flex">
                                @if ($newBreakItem)
                                    @php
                                        $data = json_decode($newBreakItem->after_time, true);
                                    @endphp

                                    {{ \Carbon\Carbon::parse($data['break_start'])->format('H:i') }}
                                    <div class="wave">～</div>
                                    {{ \Carbon\Carbon::parse($data['break_end'])->format('H:i') }}
                                @else
                                @endif
                            </span>
                        @endif
                    </div>

                    <div class="detail-row">
                        <span class="label">備考</span>

                        @if (!$latestRequest)
                            <textarea name="reason" class="remark">{{ old('reason') }}</textarea>
                        @else
                            <div class="readonly-remark">
                                {{ $latestRequest->reason }}
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

                @if (auth()->user()->role !== 'admin')
                    @if (!$latestRequest)
                        <div class="detail-button">
                            <button type="submit">修正</button>
                        </div>
                    @elseif($latestRequest->status === 'pending')
                        <p class="pending-message">
                            ※承認待ちのため修正はできません。
                        </p>
                    @elseif($latestRequest->status === 'approved')
                        <div class="detail-button">
                            <p class="approved-message">
                                承認済み
                            </p>
                        </div>
                    @endif
                @endif


            </form>

        @endif

    </div>
@endsection

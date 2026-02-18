@extends('layouts.user')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css') }}">
@endsection

@section('content')
<div class="request-inner">

    <h2 class="page-title">申請一覧</h2>

    <div class="tab-area">
        <a href="{{ route('attendance_requests.index', ['status' => 'pending']) }}"
           class="tab {{ request('status') !== 'approved' ? 'active' : '' }}">
            承認待ち
        </a>

        <a href="{{ route('attendance_requests.index', ['status' => 'approved']) }}"
           class="tab {{ request('status') === 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    <div class="table-wrapper">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td>
                            <span class="status {{ $request->status }}">
                                {{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}
                            </span>
                        </td>

                        <td>{{ $request->user->name }}</td>

                        <td>
                            {{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') }}
                        </td>

                        <td class="reason">
                            {{ $request->reason }}
                        </td>

                        <td>
                            {{ $request->created_at->format('Y/m/d') }}
                        </td>

                        <td>
                            <a href="{{ route('attendance_requests.show', $request->id) }}"
                               class="detail-link">
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">
                            申請はありません
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

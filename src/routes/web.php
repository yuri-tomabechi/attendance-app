<?php

use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    Route::get('/attendance/index', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.index');
});


Route::get('/admin/login', function () {
    if (auth()->check()) {
        auth()->logout();
    }

    return view('auth.admin-login');
})->name('admin.login');



Route::get('/redirect', function () {

    $user = auth()->user();

    if (!$user) {
        return redirect('/login');
    }

    if ($user->role === 'admin') {
        return redirect('/admin/attendance/index');
    }

    return redirect('/attendance');
})->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    Route::post('/attendance/start', [AttendanceController::class, 'startWork'])
        ->name('attendance.start');

    Route::post('/attendance/break/start', [AttendanceController::class, 'startBreak'])
        ->name('attendance.break.start');

    Route::post('/attendance/break/end', [AttendanceController::class, 'endBreak'])
        ->name('attendance.break.end');

    Route::post('/attendance/end', [AttendanceController::class, 'endWork'])
        ->name('attendance.end');

    Route::get('/attendance/list', [AttendanceController::class, 'list'])
        ->name('attendance.list');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    Route::post('/attendance/request', [AttendanceRequestController::class, 'store'])
        ->name('attendance.request.store');

    Route::get('/stamp_correction_request/list', [AttendanceRequestController::class, 'index'])
        ->name('attendance_requests.index');

    Route::get('/stamp_correction_request/{id}', [AttendanceRequestController::class, 'show'])
        ->name('attendance_requests.show');

    // Route::get('/stamp_correction_request/{id}', [AttendanceRequestController::class, 'show'])
    //     ->name('attendance_requests.show');
});

// Route::get('/attendance/detail', [AttendanceRequestController::class, 'show']);

Route::get(
    '/mylogout',
    function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
);

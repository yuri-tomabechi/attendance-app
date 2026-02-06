<?php

use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\AttendanceController;
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


    Route::post('/attendance/request', [AttendanceRequestController::class, 'store']);
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
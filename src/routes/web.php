<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminRequestController; // 追加

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
// 一般ユーザー用ログイン画面
Route::get('/login', function () {
    return view('user.auth.login');
})->middleware('guest')->name('login'); // guestを追記

// 管理者用ログイン画面
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->middleware('guest')->name('admin.login'); // guestを追記

// ログイン済みユーザーのみアクセス可能なグループ
Route::middleware(['auth'])->group(function () {

    // 振り分け
    Route::get('/dashboard', function () {
        if (Auth::user()->role === 'admin') {
                // 文字列ではなく、route() 関数を使って確実に飛ばす
                return redirect()->route('admin.attendance.index');
            }
            return redirect()->route('user.attendance.create');
    });

    // 一般ユーザー向け勤怠ルート
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('user.attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'punch'])->name('user.attendance.punch');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('user.attendance.index');
    Route::get('/attendance/detail/{id?}', [AttendanceController::class, 'show'])->name('user.attendance.show');
    Route::post('/attendance/detail/{id?}', [AttendanceController::class, 'store'])->name('user.attendance.store');

    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
        
        // 【追記】修正申請の一覧画面と承認処理（これから作成するもの）
        Route::get('/stamp_correction_request/list', [AdminRequestController::class, 'index'])->name('admin.request.index');
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'show'])->name('admin.request.show');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'approve'])->name('admin.request.approve');
    });
});

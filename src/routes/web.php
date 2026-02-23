<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\Admin\AdminAttendanceController;
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
// 一般ユーザー用ログイン画面
Route::get('/login', function () {
    return view('user.auth.login'); // resources/views/auth/login.blade.php
})->name('login');

// 管理者用ログイン画面
Route::get('/admin/login', function () {
    return view('admin.auth.login'); // resources/views/admin/login.blade.php
})->name('admin.login');

// ログイン済みユーザーのみアクセス可能なグループ
Route::middleware(['auth'])->group(function () {
    
    // 振り分け
    Route::get('/dashboard', function () {
        return Auth::user()->role === 'admin' ? redirect('/admin/attendance/list') : redirect('/attendance');
    });

    // 一般ユーザー向け勤怠ルート
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('user.attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'punch'])->name('user.attendance.punch');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('user.attendance.index');
    Route::get('/attendance/detail/{id?}', [AttendanceController::class, 'show'])->name('user.attendance.show');
});

// 管理者専用ページ
Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])
    ->middleware(['auth', 'admin'])
    ->name('admin.attendance.index');

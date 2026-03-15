<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\AdminStaffController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


// 一般ユーザー用ログイン画面
Route::get('/login', function () {
    return view('user.auth.login');
})->middleware('guest')->name('login');

// 管理者用ログイン画面
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->middleware('guest')->name('admin.login');

// 2. ログイン済みユーザー向けルート
Route::middleware(['auth'])->group(function () {

    // 振り分け
    Route::get('/dashboard', function () {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.attendance.index');
        }
        return redirect()->route('user.attendance.create');
    });

    // 申請一覧（管理者・ユーザー共通）
    Route::get('/stamp_correction_request/list', [AdminRequestController::class, 'index'])->name('admin.request.index');

    // --- 🟢 一般ユーザー向け：メール認証済み（verified）のみアクセス可能 ---
    Route::middleware(['verified'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'create'])->name('user.attendance.create');
        Route::post('/attendance', [AttendanceController::class, 'punch'])->name('user.attendance.punch');
        Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('user.attendance.index');
        Route::get('/attendance/detail/{id?}', [AttendanceController::class, 'show'])->name('user.attendance.show');
        Route::post('/attendance/detail/{id?}', [AttendanceController::class, 'store'])->name('user.attendance.store');
    });

    // --- 👑 管理者向けルート ---
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
        Route::get('/admin/attendance/edit/{id}', [AdminAttendanceController::class, 'editDay'])->name('admin.attendance.detail');
        
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'show'])->name('admin.request.show');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminRequestController::class, 'approve'])->name('admin.request.approve');
        
        Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.index');
        Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'show'])->name('admin.staff.attendance');
    });
});

Route::get('/email/verify', function () {
    return view('user.auth.verify-email');
})->middleware('auth')->name('verification.notice');

// 2. メール内のリンクをクリックした時の検証処理 (これが足りなくてエラーが出ていました)
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/dashboard'); // 認証完了後の移動先
})->middleware(['auth', 'signed'])->name('verification.verify');

// 3. 認証メールの再送処理
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

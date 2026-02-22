<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AttendanceController;

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
Route::get('/attendance', [AttendanceController::class, 'create'])->name('user.attendance.create');
Route::post('/attendance', [AttendanceController::class, 'punch'])->name('user.attendance.punch');
Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('user.attendance.index');
Route::get('/attendance/detail/{id?}', [AttendanceController::class, 'show'])->name('user.attendance.show');
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function index()
    {
        // 本来はここでDBからデータを取得しますが、まずは画面が出るか確認！
        return view('admin.attendance.index');
    }
}

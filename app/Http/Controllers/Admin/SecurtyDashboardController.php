<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SecurtyDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard',['dashboard_visible' => true]);
    }
}

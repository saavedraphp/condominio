<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\ManagesHouseSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ManagesHouseSession;
    public function index(): View
    {
        $this->clearHouseSession();
        return view('user.dashboard', ['userId' => Auth::guard('web_user')->id()]);
    }
}

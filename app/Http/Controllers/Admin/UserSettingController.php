<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserSettingController extends Controller
{
    public function showUserSettingsPage(User $user): View
    {

        return view('admin.users.user_settings', ['userId' => $user->id]);
    }
}

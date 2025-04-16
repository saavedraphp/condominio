<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserSettingPageController extends Controller
{
    public function __invoke(WebUser $webUserId): View
    {

        return view('admin.users.user_settings', ['webUserId' => $webUserId->id]);
    }
}

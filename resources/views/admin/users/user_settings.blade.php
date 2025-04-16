@extends('admin.layout.master')

@section('content')
    <div id="user-settings-container">
        <user-settings
            :user-id="'{{ $webUserId }}'"
        >
        </user-settings>
    </div>
@endsection
@vite(['resources/js/admin/user-settings.js'])

@extends('admin.layout.master')
@section('content')
    <div id="user-settings-container">
        <user-settings
            :user='@json($webUser)'
        >
        </user-settings>
    </div>
@endsection
@vite(['resources/js/admin/user-settings.js'])

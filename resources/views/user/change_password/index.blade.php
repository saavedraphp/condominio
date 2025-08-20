@extends('user.layout.master')
@section('content')
    <div id="change-password-container">
        <change-password-form
            :routes = '@json($routes)'
        />

    </div>
@endsection
@vite(['resources/js/user/change-password.js'])

@extends('user.layout.master')

@section('content')
    <div id="profile-container">
        <profile
            :user-id="'{{ $userId }}'"
        ></profile>
    </div>
@endsection
@vite(['resources/js/user/profile.js'])

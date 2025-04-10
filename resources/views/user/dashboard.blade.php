@extends('user.layout.master')

@section('content')
    <div id="dashboard-container">
        <dashboard
            :user-id="'{{ $userId }}'"
        >
        </dashboard>
    </div>
@endsection
@vite(['resources/js/user/dashboard.js'])

@extends('user.layout.master')
@section('content')
    <div id="vehicles-container">
        <vehicle-list
            :user='@json($user)'
            :is-admin='@json($isAdmin)'
        >
        </vehicle-list>
    </div>
@endsection
@vite(['resources/js/user/vehicles.js'])

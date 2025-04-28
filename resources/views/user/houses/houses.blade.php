@extends('admin.layout.master')

@section('content')
    <div id="houses-container">
        <houses-list
            :user-id="'{{ $webUserId }}'"
        >
        </houses-list>
    </div>
@endsection
@vite(['resources/js/user/houses.js'])

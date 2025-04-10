@extends('admin.layout.master')

@section('content')
    <div id="users-container">
        <user-list></user-list>
    </div>
@endsection
@vite(['resources/js/admin/users.js'])


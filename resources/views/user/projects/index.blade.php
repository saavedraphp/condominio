@extends('user.layout.master')
@section('content')
    <div id="project-list-container">
        <project-list
            :is-admin="@json($isAdmin)"
            :routes = '@json($routes)'
        />
    </div>
@endsection
@vite(['resources/js/user/projects.js'])

@extends('admin.layout.master')

@section('content')
<div id="projects-container">
    <project-list
        :routes='@json($routes)'
        :is-admin="@json($isAdmin)"
    >
    </project-list>
</div>
@endsection
@vite(['resources/js/admin/projects.js'])

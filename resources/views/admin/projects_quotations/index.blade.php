@extends('admin.layout.master')

@section('content')
<div id="projects-container">
    <project-list
        :url-base="'{{ route('admin.projects.index') }}'"
    >
    </project-list>
</div>
@endsection
@vite(['resources/js/admin/projects.js'])

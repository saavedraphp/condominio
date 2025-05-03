@extends('user.layout.master')
@section('content')
    <div id="petition-list-container">
        <petition-list
            :api-base-url="'{{ $urlBase }}'"
        >
        </petition-list>
    </div>
@endsection
@vite(['resources/js/admin/petitions.js'])

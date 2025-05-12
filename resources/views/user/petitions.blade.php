@extends('user.layout.master')
@section('content')
    <div id="petition-list-container">
        <petition-list
            :api-base-url="'{{ $urlBase }}'"
            :is-admin="{{ $isAdmin ? 'true' : 'false' }}"
            :web-user-id="{{ $webUserId }}"
        >
        </petition-list>
    </div>
@endsection
@vite(['resources/js/user/petitions.js'])

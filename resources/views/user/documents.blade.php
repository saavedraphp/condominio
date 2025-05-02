@extends('user.layout.master')
@section('content')
    <div id="document-list-container">
        <document-list
            :api-base-url="'{{ $urlBase }}'"
            :is-admin="{{ $isAdmin ? 'true' : 'false' }}"
        >
        </document-list>
    </div>
@endsection
@vite(['resources/js/user/document.js'])

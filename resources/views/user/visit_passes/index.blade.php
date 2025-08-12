@extends('user.layout.master')
@section('content')
    <div id="visit-passes-container">
        <visit-pass-list
            :user='@json($webUser)'
            :house='@json($house)'
            :routes='@json($routes)'
        />
    </div>
@endsection
@vite(['resources/js/user/visit-passes.js'])

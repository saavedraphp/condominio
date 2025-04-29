@extends('user.layout.master')
@section('content')
    <div id="document-list-container">
        <document-list></document-list>
    </div>
@endsection
@vite(['resources/js/user/document.js'])

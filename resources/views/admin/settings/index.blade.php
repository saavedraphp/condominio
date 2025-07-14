@extends('admin.layout.master')

@section('content')
    <div id="settings-container">
        <settings-view></settings-view>
    </div>
@endsection
@vite(['resources/js/admin/settings.js'])

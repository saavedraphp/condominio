@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('content')
    <div id="app">
        <div>La Esquina del Vocal</div>

        <!-- Vue se montará aquí -->
        <example-component></example-component>
    </div>
@endsection

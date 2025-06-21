@extends('adminlte::page')
@section('content')
    <div id="qr-verification-container">
        <verification-page
            :status="{{ json_encode($status) }}"
            :user="{{ json_encode($user) }}"
            :debt="{{ json_encode($debt) }}"
        />
    </div>
@endsection
@push('css')
    <style>
        .main-header, .main-sidebar, .main-footer {
            display: none !important; /* Oculta barra superior, menú lateral y footer */
        }

        .content-wrapper {
            margin-left: 0 !important; /* Usa todo el ancho */
        }
    </style>
@endpush
@vite(['resources/js/user/qr-verification.js'])

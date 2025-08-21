@extends('securities.layout.master')

@section('content')
    <div id="dashboard-container">
        <div class="main-container">
            <h1>Opciones para escanear QR</h1>
            <div class="options-wrapper">
                <!-- Opción Administrador -->
                <a href="{{ url('/security/scan-pass') }}" class="option-card">
                    <img src="{{ asset('assets/images/scan-visit-pass.jpeg') }}" alt="Acceso Administrador">
                    <div class="card-footer">
                        <span>Pase de Visita</span>
                        <span class="arrow">→</span>
                    </div>
                </a>
                <a href="{{ url('/security/scan-qr') }}" class="option-card">
                    <img src="{{ asset('assets/images/scan-security.jpeg') }}" alt="Acceso Seguidad">
                    <div class="card-footer">
                        <span>Marcacíon de Vigilancia</span>
                        <span class="arrow">→</span>
                    </div>
                </a>

            </div>
        </div>
    </div>

@endsection
<style>
    body {
        padding: 0;
    }
</style>

@vite(['resources/css/image-option-scanner-securities.css'])
@vite(['resources/js/securities/dashboard.js'])

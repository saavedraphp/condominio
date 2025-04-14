@extends('adminlte::page')

@section('content')
    <div class="container">
        <h2>Establece tu contraseña</h2>

        <form method="POST" action="{{ route('account.activate') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" class="form-control" value="{{ $email }}" disabled>
            </div>

            <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Confirmar contraseña</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Activar cuenta</button>
        </form>
    </div>
@endsection
{{-- Ocultar menús y ajustar el diseño --}}
@push('css')
    <style>
        .header-login {
            max-width: 800px;
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .title {
            font-size: 46px;
            font-weight: bold;
            color: black;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 5px;
        }

        .image-container {
            width: 100%;
            max-width: 600px;

            img {
                width: 100%;
                height: auto;
                border-radius: 10px;
                box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
            }
        }

        .condominio-info {
            text-align: center;

            .name-condominio {
                font-size: 34px;
                font-weight: bold;
                color: black;
            }

            .owner-meeting {
                font-size: 22px;
                color: #444;
                margin-top: 5px;
            }
        }

        .link-style {
            color: #0c84ff;
            text-decoration: underline;
            cursor: pointer;
        }

        .main-header, .main-sidebar, .main-footer {
            display: none !important; /* Oculta barra superior, menú lateral y footer */
        }

        .content-wrapper {
            margin-left: 0 !important; /* Usa todo el ancho */
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            margin: auto;
        }
    </style>
@endpush
@section('adminlte_js')
    @vite(['resources/js/app.js'])
@endsection

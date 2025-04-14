@extends('adminlte::page')

@section('content')
    <div class="header-login">
        <div>
        <h1 class="title">
            <span>La Esquina del Vocal</span>
        </h1>
        </div>
        <div class="image-container">
            <img src="{{ asset('assets/images/imagen-login-condominio.jpg') }}" alt="Condominio Imagen">
        </div>

        <div class="condominio-info">
            <h2 class="name-condominio">Propietarios de Islas Cerdeñas</h2>
            <p class="owner-meeting">Junta de propietarios y asociados</p>
        </div>
    </div>

    <div class="login-box">
        <div class="login-logo">

        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Inicia sesión para comenzar</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('user.login') }}">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember">
                                <label for="remember"> Recordarme </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <a class="link-style">Olvide mi Contraseña</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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

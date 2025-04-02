@extends('adminlte::page')

@section('content')
@endsection
@section('adminlte_sidebar')
    aaaa
    <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <li class="nav-item">
                <a href="{{ route('user.dashboard') }}" class="nav-link">
                    <i class="nav-icon fas fa-home"></i>
                    <p>Inicio</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="" class="nav-link">
                    <i class="nav-icon fas fa-user"></i>
                    <p>Mi Perfil</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="" class="nav-link">
                    <i class="nav-icon fas fa-calendar-check"></i>
                    <p>Mis Citas</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('logout') }}" class="nav-link">
                    <i class="nav-icon fas fa-sign-out-alt"></i>
                    <p>Cerrar Sesión</p>
                </a>
            </li>
        </ul>
    </nav>
@endsection
@section('adminlte_js')
    @vite(['resources/js/app.js'])
@endsection

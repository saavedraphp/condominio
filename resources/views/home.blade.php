<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <!-- Si usas AdminLTE, sus estilos ya estarán cargados -->
    <!-- Si no, necesitarás un CSS básico o el que te proporciono abajo -->
    </head>

<body>

<div class="home-container">
    <div class="options-wrapper">

        <!-- Opción Propietario -->
        <a href="{{ url('/user/login') }}" class="option-card">
            <img src="{{ Vite::asset('resources/user/images/propietario.jpg') }}" alt="Acceso Propietario"> <!-- Cambia la ruta a tu imagen -->
            <div class="card-footer">
                <span>Propietario</span>
                <span class="arrow">→</span>
            </div>
        </a>

        <!-- Opción Administrador -->
        <a href="{{ url('/admin/login') }}" class="option-card">
            <img src="{{ Vite::asset('resources/user/images/administrador.jpg') }}" alt="Acceso Administrador"> <!-- Cambia la ruta a tu imagen -->
            <div class="card-footer">
                <span>Administrador</span>
                <span class="arrow">→</span>
            </div>
        </a>

    </div>
</div>
</body>
</html>
@vite(['resources/css/home-options.css'])

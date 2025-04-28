<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        {{-- Elementos Básicos --}}
        <li class="nav-item">
            <a href="" class="nav-link {{ Request::is('user/dashboard*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="" class="nav-link {{ Request::is('casas*') && !session('selected_casa_id') ? 'active' : '' }}">
                <i class="nav-icon fas fa-home"></i>
                <p>Casas del Propietario</p>
            </a>
        </li>
        {{-- Otros elementos básicos aquí --}}


        {{-- Elementos Condicionales (si hay casa seleccionada) --}}
        @if(session('selected_casa_id'))
            @php $casaId = session('selected_casa_id'); @endphp
            <li class="nav-header text-uppercase">GESTIÓN: {{ session('selected_casa_nombre', 'CASA') }}</li>

            {{-- Ejemplo: Detalles Casa --}}
            <li class="nav-item">
                {{-- Marca activa si la ruta actual es la de detalles de *esta* casa --}}
                <a href="{{ route('casas.show', $casaId) }}" class="nav-link {{ Request::routeIs('casas.show') && request()->route('casa')->id == $casaId ? 'active' : '' }}">
                    <i class="nav-icon fas fa-info-circle"></i>
                    <p>Detalles de la Casa</p>
                </a>
            </li>

            {{-- Ejemplo: Habitaciones --}}
            <li class="nav-item">
                {{-- Marca activa si la ruta actual empieza con casas/{id}/habitaciones --}}
                <a href="{{ route('casas.habitaciones.index', $casaId) }}" class="nav-link {{ Request::is("casas/{$casaId}/habitaciones*") ? 'active' : '' }}">
                    <i class="nav-icon fas fa-door-open"></i>
                    <p>Habitaciones</p>
                </a>
            </li>

            {{-- Ejemplo: Submenú Configuración --}}
            <li class="nav-item {{ Request::is("casas/{$casaId}/tarifas*") || Request::is("casas/{$casaId}/politicas*") ? 'menu-open' : '' }}">
                <a href="#" class="nav-link {{ Request::is("casas/{$casaId}/tarifas*") || Request::is("casas/{$casaId}/politicas*") ? 'active' : '' }}">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>
                        Configuración
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('casas.tarifas.index', $casaId) }}" class="nav-link {{ Request::is("casas/{$casaId}/tarifas*") ? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i> {{-- O icono específico --}}
                            <p>Tarifas</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('casas.politicas.index', $casaId) }}" class="nav-link {{ Request::is("casas/{$casaId}/politicas*") ? 'active' : '' }}">
                            <i class="far fa-circle nav-icon"></i>
                            <p>Políticas</p>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Agrega los demás elementos aquí --}}

        @endif

        {{-- Aquí iría la lógica de roles que ya tenías --}}
        {{-- @can('view-admin-section') ... @endcan --}}

    </ul>
</nav>
@section('content')

@endsection
@section('adminlte_js')
    @vite(['resources/js/app.js'])
@endsection

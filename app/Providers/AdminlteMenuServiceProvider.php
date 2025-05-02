<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

class AdminlteMenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(Dispatcher $events) // Inyecta el Dispatcher
    {
        // Escucha el evento BuildingMenu que dispara el paquete AdminLTE
        $events->listen(BuildingMenu::class, function (BuildingMenu $event) {

            // --- Menú Básico (Siempre visible) ---
            $event->menu->add([
                'text' => 'Inicio',
                'url' => 'user/dashboard', // Cambia a tu ruta
                'icon' => 'fas fa-fw fa-tachometer-alt',
                'can' => 'view_payment_history',
            ]);
            $event->menu->add([
                'text' => 'Casas',
                'url' => 'user/houses/list',
                'icon' => 'fas fa-fw fa-home',
                'can' => 'view_payment_history',
            ]);

            // --- Aquí puedes agregar otros items básicos ---
            // ...

            // --- Menú Condicional (Solo si hay una casa seleccionada) ---
            $selectedHouseId = session('selected_house_id');
            $selectedHouseName = session('selected_house_name', 'Casa Seleccionada'); // Nombre por defecto

            if ($selectedHouseId) {
                $event->menu->add([
                    'header' => strtoupper("GESTIÓN: {$selectedHouseName}"), // Encabezado dinámico
                ]);
                $event->menu->add([
                    'text' => 'Historial de Pagos',
                    'url' => "user/houses/{$selectedHouseId}/payments/list",
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Recivos de Mantenimiento',
                    'url' => 'admin/blog',
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Comsumo de luz',
                    'url' => "user/houses/{$selectedHouseId}/electricity-records/list",
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Comsumo de agua',
                    'url' => "user/houses/{$selectedHouseId}/water-records/list",
                    'can' => 'view_payment_history',
                ]);

                // --- Agrega aquí todas las demás opciones específicas de la casa ---
                $event->menu->add([
                    'text' => 'Configuración',
                    'icon' => 'fas fa-fw fa-cogs',
                    'can' => 'view_payment_history--',
                    'submenu' => [
                        [
                            'text' => 'Tarifas',
                            'url' => "admin/casas/{$selectedHouseId}/tarifas",
                            'icon' => 'fas fa-fw fa-dollar-sign',
                        ],
                        [
                            'text' => 'Políticas',
                            'url' => "admin/casas/{$selectedHouseId}/politicas",
                            'icon' => 'fas fa-fw fa-file-alt',
                        ],
                    ],
                ]);
            }

            if (!Request::is('user/houses/*')) {
                $event->menu->add([
                    'text' => 'Perfil',
                    'url' => 'user/profile',
                    'icon' => 'fas fa-fw fa-user',
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Mi (Codigo QR)',
                    'url' => 'admin/blog',
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Peticiones al Vocal',
                    'url' => 'admin/blog',
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Presupuesto vs Gastos',
                    'url' => 'admin/blog',
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Docs del Condominio',
                    'route' => 'user.documents.show-page',
                    'can' => 'view_payment_history',
                ]);
                $event->menu->add([
                    'text' => 'Cotizaciones Presentadas',
                    'url' => 'admin/blog',
                    'can' => 'view_payment_history',
                ]);

            }
            //  'route' => ['nombre.ruta.con.parametro', ['parametro_id' => $algunaVariableId]], // Pasa parámetros como array
            // --- COMBINAR CON LÓGICA DE ROLES ---
            // Si ya tenías lógica de roles aquí, asegúrate de que coexista.
            // Por ejemplo, un item podría requerir un rol Y que una casa esté seleccionada.
            // if ($selectedHouseId && auth()->user()->hasRole('admin_casa')) { ... }
        });
    }
}

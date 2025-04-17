<?php

namespace App\Listeners;
use Illuminate\Support\Facades\Route;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use Illuminate\Contracts\Auth\Factory as AuthFactory; // Inyecta Auth Factory



use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BuildAdminlteMenu
{
    protected $auth;
    /**
     * Create the event listener.
     */
    // Inyecta Auth Factory para acceder a los guards
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle the event.
     */
    public function handle(BuildingMenu $event): void
    {
        // Define la ruta y el texto base para el logout
        $logoutRouteName = null;
        $logoutText = __('adminlte::menu.logout'); // Usa la traducción por defecto o la tuya
        $logoutIcon = 'fas fa-fw fa-sign-out-alt'; // Icono estándar

        if ($this->auth->guard('web')->check()) {
            $logoutRouteName = 'admin.logout';
        } elseif ($this->auth->guard('web_user')->check()) {
            $logoutRouteName = 'user.logout';
        }

        // Si se determinó una ruta de logout (alguien está logueado)
        if ($logoutRouteName && Route::has($logoutRouteName)) { // Verifica que la ruta exista

            // Opción A: Si tienes un item 'logout' FIJO en config/adminlte.php, MODIFÍCALO.
            // Necesitarías darle una 'key' única en la config para encontrarlo fácil.
            // Ejemplo: $event->menu->find('logout_url')['route'] = $logoutRouteName;

            // Opción B: ELIMINAR cualquier item de logout preexistente y AÑADIR el correcto.
            // Es más seguro si no estás seguro de cómo está la config.
            $event->menu->remove($logoutText); // Intenta remover un item con key/text 'logout' si existe
            // Puedes necesitar iterar y remover por texto si no usas keys:
             foreach ($event->menu->menu as $index => $item) {
                 if (isset($item['text']) && strtolower($item['text']) === strtolower($logoutText)) {
                     unset($event->menu->menu[$index]);
                 }
             }


            // Añade el item de logout dinámico al final del menú
            $event->menu->add([
                'text'   => $logoutText,
                'route'  => $logoutRouteName, // Usa 'route' si tienes rutas nombradas
                'url' => route($logoutRouteName), // Alternativa si no usas 'route' en la config
                'method' => 'GET',          // Importante indicar que es POST
                'icon'   => $logoutIcon,
                'topnav_right' => true, // Opcional: si quieres que aparezca arriba a la derecha en topnav
            ]);
        } else {
            // Opcional: Si no hay nadie logueado O la ruta no existe, asegúrate
            // de que no haya un enlace de logout visible (remuévelo si existe).
            $event->menu->remove('logout_url');
            // O itera como arriba para remover por texto.
        }

    }
}

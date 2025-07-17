<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    private $statisticsService;

    public function __construct()
    {
        $this->statisticsService = app(StatisticsService::class);
    }

    public function getStatisticCards(): JsonResponse
    {
        $associatedUsersCount = $this->statisticsService->getAssociatedUsersCount();
        $nroDepartments = $this->statisticsService->getNroDepartments();
        $statsData = [
            [
                'title' => 'Total de Asociados',
                'value' => $associatedUsersCount,
                'icon' => 'mdi mdi-account-star', // Icono de Material Design Icons
                'color' => 'teal',
                'to' => '/orders' // Ruta para Vue Router
            ],
            [
                'title' => 'Total Departamentos',
                'value' => $nroDepartments,
                //'suffix' => '%', // El frontend manejará este sufijo opcional
                'icon' => 'mdi mdi-home-floor-0',
                'color' => 'green',
                'to' => '/analytics'
            ],
/*            [
                'title' => 'User Registrations',
                'value' => 0,
                'icon' => 'mdi-account-plus',
                'color' => 'yellow-darken-2', // Colores de Vuetify
                'to' => '/users'
            ],
            [
                'title' => 'Unique Visitors',
                'value' => 0,
                'icon' => 'mdi-chart-pie',
                'color' => 'red',
                'to' => '/visitors'
            ],*/
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statsData
        ], JsonResponse::HTTP_OK);

    }

}

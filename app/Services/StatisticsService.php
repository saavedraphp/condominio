<?php

namespace App\Services;

use App\Models\House;
use App\Models\WebUser;

class StatisticsService
{
    public function getAssociatedUsersCount(): int
    {
        // Aquí puedes añadir caché en el futuro si es necesario
        return WebUser::query()
            ->where('is_associated', true)
            ->count();
    }

    public function getNroDepartments(): int
    {
        return House::query()
            ->where('is_department', true)
            ->count();
    }
}

<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case OK = 'ok';
    case INCIDENT = 'incident';
    case DELAYED = 'delayed';

    // Puedes agregar un método para obtener una descripción legible si lo necesitas
    public function label(): string
    {
        return match ($this) {
            self::OK => 'All Clear',
            self::INCIDENT => 'Incident Reported',
            self::DELAYED => 'Delayed Check-in',
        };
    }
}

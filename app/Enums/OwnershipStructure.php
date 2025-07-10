<?php


namespace App\Enums;
enum OwnershipStructure: string
{
    // Los casos corresponden a los valores que guardas en la base de datos
    case OWNERS_BOARD = 'owners_board';
    case ASSOCIATION_ONLY = 'association_only';
    case OWNERS_BOARD_WITH_ASSOCIATION = 'owners_board_with_association';

    /**
     * Devuelve la etiqueta legible por humanos para la UI.
     * Este método reemplaza tu función de JavaScript.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNERS_BOARD => 'JP Isla cerdeña',
            self::ASSOCIATION_ONLY => 'Asociación I.S.P',
            self::OWNERS_BOARD_WITH_ASSOCIATION => 'Junta y Asociación',
        };
    }
}

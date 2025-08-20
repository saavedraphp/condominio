<?php

namespace App\Exports;

use Illuminate\Support\Collection; // Importante para el type-hinting
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// ¡Extra! Para que las columnas se ajusten solas

class DebtsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithColumnFormatting
{
    use Exportable;

    protected $debtorData;

    /**
     * El constructor recibe la colección ya procesada desde el controlador.
     *
     * @param Collection $debtorData
     */
    public function __construct(Collection $debtorData)
    {
        $this->debtorData = $debtorData;
    }

    /**
     * Devuelve la colección que se usará para generar el Excel.
     * Este método es requerido por la interfaz FromCollection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->debtorData;
    }

    /**
     * Define las cabeceras del archivo Excel.
     * Deben coincidir con la estructura de tu ->map() en el controlador.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'DIRECCIÓN',
            'PROPIETARIO',
            'ARREGLO DE PAGO',
            'TOTAL',
        ];
    }

    public function map($row): array
    {
        return [
            $row['address'],
            $row['owner'],
            $row['has_payment_arrangement'],
            $row['amount_due'],
        ];
    }

    public function columnFormats(): array
    {
        // La letra de la columna debe coincidir con el orden de tus headings/map
        return [
            //'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Formato: #,##0.00
            'D' => '[Red]#,##0.00;[Red]-#,##0.00;"0.00"',
        ];
    }
}

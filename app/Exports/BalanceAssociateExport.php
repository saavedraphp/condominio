<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class BalanceAssociateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithColumnFormatting, WithStrictNullComparison
{
    use Exportable;

    protected $debtorData;

    public function __construct(Collection $debtorData)
    {
        $this->debtorData = $debtorData;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return $this->debtorData;
    }

    public function headings(): array
    {
        return [
            'ASOCIADO',
            'PROPIEDAD',
            'DEUDA TOTAL',
        ];
    }

    public function map($row): array
    {

        $totalDue = is_numeric($row['total_due']) ? (float) $row['total_due'] : 0.0;

        return [
            $row['user_name'],
            $row['houses_addresses'],
            $totalDue,
        ];
    }

    public function columnFormats(): array
    {
        // La letra de la columna debe coincidir con el orden de tus headings/map
        return [
            //'C' => '#,##0.00;[Red]-#,##0.00;0.00',
            'C' => '[Red]#,##0.00;[Green]-#,##0.00;[Green]0.00',
        ];
    }


}

<?php

namespace App\Http\Controllers;

use App\Models\AnnualBudget;
use App\Models\House;
use App\Models\HouseMonthlyCharge;
use App\Models\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Backtrace\Arguments\Reducers\DateTimeArgumentReducer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseMonthlyChargeController extends Controller
{
    const TYPE_HOUSE_ASSOCIATED = 'association_only';
    const TYPE_HOUSE_BOARD_ASSOCIATED = 'owners_board_with_association';
    const TYPE_HOUSE_BOARD = 'owners_board';
    const NRO_ASSOCIATED = 93;
    const FEE_JP_SARDINIA_ISLANDS = 334.66; // Cuota de la Asociación
    const BUILDING_FUNDS = 125.66;

    const FEET_IS_LOT = 150.00; // Cuota de la Asociación para lotes, si aplica
    const PRICE_BY_KWH = 0.625;

    public function showPage(): View
    {
        return view('admin.house_monthly_charge.index');
    }

    public function index(Request $request): JsonResponse
    {
        // Iniciar la consulta
        $query = HouseMonthlyCharge::query();

        // Cargar la relación con 'house' para obtener el nombre de la casa
        // y con 'details' si necesitas alguna información agregada de los detalles (opcional aquí)
        $query->with('house:id,address,ownership_structure'); // Carga selectiva de columnas de house

        // --- Filtrado (Ejemplos) ---
        if ($request->has('house_id') && $request->input('house_id') != '') {
            $query->where('house_id', $request->input('house_id'));
        }

        if ($request->has('period_year') && $request->input('period_year') != '') {
            $query->where('period_year', $request->input('period_year'));
        }

        if ($request->has('period_month') && $request->input('period_month') != '') {
            $query->where('period_month', $request->input('period_month'));
        }

        if ($request->has('status') && $request->input('status') != '') {
            $query->where('status', $request->input('status'));
        }

        // Búsqueda por nombre de casa (si tienes un campo de búsqueda general)
        if ($request->has('search') && $request->input('search') != '') {
            $searchTerm = $request->input('search');
            $query->whereHas('house', function ($q) use ($searchTerm) {
                $q->where('address', 'like', '%' . $searchTerm . '%');
            });
        }

        // --- Ordenación (Ejemplos) ---
        // Por defecto, ordenar por los más recientes (año y mes descendente)
        $sortBy = $request->input('sort_by', 'period_year'); // Columna por la que ordenar
        $sortDirection = $request->input('sort_direction', 'desc'); // Dirección de ordenación

        if ($sortBy === 'house_name') { // Si quieres ordenar por nombre de casa
            $query->join('houses', 'house_monthly_charges.house_id', '=', 'houses.id')
                ->orderBy('houses.address', $sortDirection)
                ->select('house_monthly_charges.*'); // Evitar ambigüedad de 'id'
        } else if (in_array($sortBy, ['period_year', 'period_month', 'total_amount', 'status', 'issued_date', 'due_date'])) {
            $query->orderBy($sortBy, $sortDirection);
            // Si el orden primario es por año, un orden secundario por mes es útil
            if ($sortBy === 'period_year' && $sortDirection === 'desc') {
                $query->orderBy('period_month', 'desc');
            } elseif ($sortBy === 'period_year' && $sortDirection === 'asc') {
                $query->orderBy('period_month', 'asc');
            }
        } else {
            // Orden por defecto si no se especifica o no es válido
            $query->orderBy('period_year', 'desc')->orderBy('period_month', 'desc');
        }

        // --- Paginación ---
        $perPage = $request->input('per_page', 15); // Número de ítems por página
        $monthlyCharges = $query->paginate($perPage);

        // Transformar los datos para la respuesta si es necesario
        // (ej. para formatear el nombre del mes)
        $monthlyCharges->getCollection()->transform(function ($charge) {
            // Crear un nombre de período legible
            // Carbon::createFromDate($charge->period_year, $charge->period_month, 1)->isoFormat('MMMM YYYY'); // Requiere locale configurado
            $charge->period_name = Carbon::create()->month($charge->period_month)->format('F') . ' ' . $charge->period_year; // "October 2023"

            // Puedes añadir más transformaciones aquí si es necesario
            // Por ejemplo, si quieres cargar selectivamente algunos detalles para un resumen
            // $charge->num_items = $charge->details()->count(); // Esto haría N+1 si no se optimiza

            return $charge;
        });

        return response()->json($monthlyCharges);
    }

    public function preparedData(array $input): array
    {
        $house_id = $input['house_id'];
        $year = $input['year'] ?? date('Y');
        $month = $input['month'] ?? date('m');
        $preview = $input['is_preview'];

        $house = House::query()
            ->where('id', $house_id)
            ->with('owner')
            ->firstOrFail();
        $houseArray = $house->toArray();
        $typeHouse = $house->ownership_structure;
        $balanceHouse = $this->getBalanceHouse($house->id);

        $monthInput = (int)$month;
        $historyElectric = $this->getConsumptions($house_id);
        $matrix = $this->makeMatrixConsumption($historyElectric['consumptionDetails']);
        if ($preview === "true") {
            $logoPath = asset('assets/images/logo.jpg');
            $tablaImagePath = asset('assets/images/statistical-table.jpg');
        } else {
            $logoPath = storage_path('app/public/file_paths/profile/nVcxTYTvFIndE6SVndfDMUTG6uFp5CPcCSFKhmFc.jpg');
            $tablaImagePath = storage_path('app/public/file_paths/profile/VYdqO7AcgJJ0j26HUKaNyfW278Hi2ex2oEuNgwNZ.jpg');
        }

        Carbon::setLocale('es');
        $now = Carbon::now()->locale('es');
        $firstDay = $now->copy();
        $firstDay->startOfMonth();
        $date_emitted = $firstDay->translatedFormat('F d, Y');
        $months = $this->getMonthSpanish();
        $data = [
            'house_id' => $house_id,
            'is_type_house_board' => $typeHouse === self::TYPE_HOUSE_BOARD,
            'show_table_energy' => ($typeHouse === self::TYPE_HOUSE_ASSOCIATED && (bool)$house['is_lot'] === false),
            'house_type' => $typeHouse,
            'period_year' => $year,
            'period_month' => strtolower($months[$monthInput]),
            'period_month_number' => $monthInput,
            'date_emitted' => $date_emitted,
            'associated' => [
                'name' => $house->owner->first()?->name ?? 'No disponible',
                'property' => $house->address,
            ],
            'electrical_history_table' => $matrix,
            'logoPath' => $logoPath,
            'title' => 'ASOCIACION DE PROPIETARIOS ISLAS DE SAN PEDRO',
            'tablaImagePath' => $tablaImagePath,
            'debt' => empty($balanceHouse['opening_balance']) ? 'Pendiente a Revisión' : number_format($balanceHouse['amount_due'], 2),
            'bank_name' => 'BANCO CREDITO DEL PERU (BCP)',
            'bank_account' => '194-72597403-0-08',
            'bank_account_cci' => '00219417259740300893',
            'bank_account_name' => 'Rudy Huaranga - Francis Iturbe',
            'ruc_assoc_prop_isp' => 'RUC: 20525153861',

        ];
        $data['details'] = [];

        if ($typeHouse === self::TYPE_HOUSE_ASSOCIATED || $typeHouse === $this::TYPE_HOUSE_BOARD_ASSOCIATED) {
            $data['details'] = $this->getDetails($typeHouse, $houseArray);
        }

        $amount_of_month = array_sum(array_column($data['details'], 'amount'));
        $data = $data + [
                'amount_month' => $amount_of_month,
                'total_debt' => $balanceHouse['amount_due'] + $amount_of_month,
                'issued_date' => now()->format('d/m/Y'),
                'due_date' => now()->addDays(30)->format('d/m/Y'),
            ];
        $amount_of_month = number_format($amount_of_month, 2);

        if ($typeHouse === self::TYPE_HOUSE_ASSOCIATED) {

            $data = $data + [
                    'paragraph_amount' => "El monto de mantenimiento por el mes de {$data['period_month']} es de <strong>S/{$amount_of_month}.</strong> Adjunto encontrara los detalles de su recibo. Le solicitamos que realice el pago correspondiente a la cuenta bancaria aprobada por la Asociación en asamblea a nombre del presidente de la asociación el Señor Rudy David Huaranga Bolaños:",
                    'paragraph_thank_you' => "Agradecemos su colaboración y puntualidad, le recordamos que una ves la asociación recupere sus poderes todas las deudas pendientes serán reportadas a los sistemas
                                              financieros peruanas.  Si tienen alguna pregunta o necesitan asistencia adicional, no duden en ponerse en contacto con nosotros.",
                    'title_details_line_1' => 'RECIBO POR MANTENIMIENTO – ' . strtoupper($data['period_month']) . ' ' . $data['period_year'],
                    'title_details_line_2' => 'ASOCIACION DE PROPIETARIOS ISLAS DE SAN PEDRO',
                    'contact_email' => 'isp.asociacion@gmail.com',
                ];
        } else if ($typeHouse === self::TYPE_HOUSE_BOARD_ASSOCIATED) {
            $data = $data + [
                    'paragraph_amount' => "El monto de asociado solamente del mes de {$data['period_month']} es de <strong>S/{$amount_of_month}.</strong> Les solicitamos que
                                            realicen el pago correspondiente a la cuenta bancaria aprobada a nombre del presidente de
                                            la asociación el Señor Rudy David Huaranga Bolaños: ",
                    'paragraph_thank_you' => "Agradecemos su colaboración y puntualidad. Si tienen alguna pregunta o necesitan asistencia adicional, no duden en ponerse en contacto con nosotros.",
                    'title_details_line_1' => 'RECIBO DE ASOCIADO',
                    'title_details_line_2' => 'ASOCIACION DE PROPIETARIOS ISLAS DE SAN PEDRO',
                    'contact_email' => 'isp.asociacion@gmail.com',
                ];
        } else if ($typeHouse === self::TYPE_HOUSE_BOARD) {

            $data = $data + [
                    'paragraph_amount' => "El monto de asociado solamente del mes de {$data['period_month']} es de <strong>S/{$amount_of_month}.</strong> Les solicitamos que
                                            realicen el pago correspondiente a la cuenta bancaria aprobada  por la Junta de Propietarios:",
                    'paragraph_thank_you' => "Agradecemos su colaboración y puntualidad. Si tienen alguna pregunta o necesitan asistencia adicional, no duden en ponerse en contacto con nosotros.",
                    'title_details_line_1' => 'RECIBO DE MANTENIMIENTO COMUN',
                    'title_details_line_2' => 'JUNTA DE PROPIETARIOS ISLAS CERDENA </br>
LOTE ACUMULADO C-39A',
                    'contact_email' => 'asociacion.islas@gmail.com',
                ];
            $data = array_merge($data, [
                'title' => 'JUNTA DE PROPIETARIOS ISLAS CERDENA',
                'accumulated_lot' => 'LOTE ACUMULADO C-39A',
                'bank_name' => 'INTERBANK',
                'bank_account' => 'XXXX',
                'bank_account_cci' => 'XXXX',
                'bank_account_name' => 'JUNTA DE PROPIETARIOS LOTE ACUMULADO C-39',
            ]);
        }

        return $data;
    }

    public function generateAndStore(Request $request): JsonResponse
    {
        try {
            $data = $this->preparedData($request->all());
            $viewToLoad = 'pdf.receipt_all';

            $pdf = Pdf::loadView($viewToLoad, $data);

            // 3. Definir la ruta y nombre del archivo
            $year = $data['period_year'];
            $month = str_pad($data['period_month'], 2, '0', STR_PAD_LEFT);
            $fileName = "recibo-propiedad-{$data['house_id']}-{$year}-{$month}.pdf";
            $filePath = "file_paths/receipts/{$year}/{$month}/{$fileName}";

            // 4. Guardar el archivo en el disco de storage
            Storage::disk('public')->put($filePath, $pdf->output());

            // 5. Guardar el registro en la base de datos
            // updateOrCreate es perfecto para evitar duplicados
            $now = Carbon::now();
            $issuedDate = $now->copy()->startOfMonth();
            $dueDate = $now->copy()->startOfMonth()->addDays(14);
            HouseMonthlyCharge::updateOrCreate(
                [
                    'house_id' => $data['house_id'],
                    'period_year' => $data['period_year'],
                    'period_month' => $data['period_month_number'],
                ],
                [
                    'total_amount' => $data['amount_month'], // El monto calculado
                    'pdf_path' => $filePath,
                    'issued_date' => $issuedDate->format('Y-m-d'), // Fecha de emisión
                    'due_date' => $dueDate->format('Y-m-d'), // Fecha de vencimiento, por ejemplo 30 días después
                    // otros campos que necesites guardar...
                ]
            );

            // 6. Devolver respuesta de éxito
            return response()->json([
                'success' => true,
                'message' => 'Recibo generado y registrado exitosamente.',
                'path' => $filePath,
            ], 201); // 201 Created

        } catch (\Exception $e) {
            Log::error('Error al guardar los datos' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error en el servidor al generar el recibo.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function previewPdfReceipt(Request $request): view
    {
        // associated, board_associate, board
        //$typeHouse = $request->input('type_house', self::TYPE_HOUSE_ASSOCIATED);
        $house_id = $request->input('house_id', 18); // ID de la casa, por defecto 18
        $year = $request->input('year', date('Y')); // Año actual por defecto
        $monthInput = (int)$request->input('month', date('m')); // Mes actual por defecto

        $data = $this->preparedData($request->all());
        $viewToLoad = 'pdf.receipt_all';
        return view($viewToLoad, $data);

    }

    public function getDetails(string $type, array $house): array
    {
        if ($this::TYPE_HOUSE_BOARD === $type) {
            return [];
        }
        $fee_association_ISP = $this->getFeeAssociationISP();

        if ($type === self::TYPE_HOUSE_ASSOCIATED) {
            if ($house['is_lot']) {
                return $this->getLotDetails($fee_association_ISP);
            }

            $details = $this->getDepartmentEnergyDetails($house, $fee_association_ISP);
            return $this->addPompeyaFundIfApplies($details, $house);
        }

        if ($type === self::TYPE_HOUSE_BOARD_ASSOCIATED) {
            return $this->getQuoteAssociated();
        }

        return [];

    }

    private function getDepartmentEnergyDetails(array $house, float $fee_association_ISP): array
    {
        $consumptionEnergy = PaymentService::query()
            ->where([
                ['service_id', 1],
                ['house_id', $house['id']],
            ])
            ->whereYear('payment_date', Carbon::now()->year)
            ->whereMonth('payment_date', Carbon::now()->subMonth()->month)
            ->latest('payment_date')
            ->first();

        $amountElectric = $consumptionEnergy
            ? $consumptionEnergy->consumption * self::PRICE_BY_KWH
            : 0;

        return [
            [
                'title' => 'Consumo de Luz personal',
                'amount' => $amountElectric
            ],
            [
                'title' => 'Cuota a la Asociación I.S.P',
                'amount' => $fee_association_ISP
            ],
            [
                'title' => 'Cuota a la J.P. Isla Cerdeña',
                'amount' => self::FEE_JP_SARDINIA_ISLANDS - self::BUILDING_FUNDS - $fee_association_ISP
            ]
        ];
    }

    private function getFeeAssociationISP(): float
    {
        $details = $this->getQuoteAssociated();
        return array_sum(array_column($details, 'amount'));
    }

    private function getLotDetails(float $fee_association_ISP): array
    {
        return [
            [
                'title' => 'Cuota a la Asociación I.S.P',
                'amount' => $fee_association_ISP
            ],
            [
                'title' => 'Cuota a la J.P. Isla Cerdeña',
                'amount' => self::FEET_IS_LOT - $fee_association_ISP
            ]
        ];
    }

    public function getQuoteAssociated(): array
    {
        $annualBudgets = AnnualBudget::with('budgetType')
            ->where('year', Carbon::now()->year)
            ->get();

        return $annualBudgets->map(function ($budget) {
            return [
                'title' => $budget->budgetType->name,
                'amount' => ($budget->amount / 12) / self::NRO_ASSOCIATED,
            ];
        })->toArray();

    }

    private function addPompeyaFundIfApplies(array $details, array $house): array
    {
        if (!empty($house['is_department'])) {
            array_unshift($details, [
                'title' => 'Fondos del Edificio Pompeya',
                'amount' => self::BUILDING_FUNDS
            ]);
        }

        return $details;
    }

    public function getConsumptions(int $house_id): array
    {
        // --- 1. Definir el Rango de Fechas ---
        /*// Carbon es una librería para manejar fechas que viene con Laravel. Es muy potente.
        $endDate = Carbon::now()->startOfMonth(); // Inicio del mes actual (ej. 1 de Noviembre 2023)
        $startDate = $endDate->copy()->subMonths(11); // Retrocedemos 11 meses para tener un periodo de 12 meses. (ej. 1 de Diciembre 2022)*/

        // Fecha inicio: 1 de diciembre del año pasado
        $startDate = Carbon::createFromDate(now()->year - 1, 12, 15)->startOfDay();
        // Fecha fin: último día de noviembre del año actual
        $endDate = Carbon::createFromDate(now()->year, 11, 15)->endOfMonth()->endOfDay();

        // Esto nos da un rango exacto de 12 meses.
        // Por ejemplo, de Dic-2022 a Nov-2023.

        // --- 2. Crear el Array "Plantilla" con los 12 Meses ---
        // Este array contendrá los 12 meses que queremos mostrar, con consumo 0 por defecto.
        $monthlyTemplate = [];
        $dateIterator = $startDate->copy(); // Creamos una copia para no modificar la fecha de inicio

        // Nombres de los meses en español para la vista
        /*        $spanishMonths = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ];*/
        $spanishMonths = $this->getMonthSpanish();

        while ($dateIterator->lessThanOrEqualTo($endDate)) {
            $year = $dateIterator->year;
            $month = $dateIterator->month;

            // Usamos una clave 'Año-Mes' para facilitar la búsqueda después
            $key = "$year-$month";

            $monthlyTemplate[$key] = [
                'year' => $year,
                'month_name' => $spanishMonths[$month],
                'title' => $spanishMonths[$month] . ' ' . $year, // Título listo para la vista
                'consumption' => 'N/A' // Valor por defecto
            ];

            $dateIterator->addMonth(); // Pasamos al siguiente mes
        }

        // --- 3. Consultar la Base de Datos ---
        // Traemos solo los meses que SÍ tienen un registro en la BD dentro de nuestro rango.
        // Asumiendo que la tabla se llama 'consumos' y el campo de fecha 'fecha_lectura'
        $dbConsumptions = PaymentService::query()
            ->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(consumption) as total_consumption') // Usamos SUM por si hay varios registros en un mes
            )
            ->where('house_id', $house_id)
            ->where('service_id', 1)
            ->whereBetween('payment_date', [$startDate, $endDate->copy()->endOfMonth()])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // --- 4. Combinar los Datos ---
        // Recorremos los resultados de la BD y actualizamos nuestra plantilla.
        foreach ($dbConsumptions as $consumption) {
            $key = "{$consumption->year}-{$consumption->month}";
            if (isset($monthlyTemplate[$key])) {
                $monthlyTemplate[$key]['consumption'] = $consumption->total_consumption;
            }
        }

        // --- 5. Preparar Datos para la Vista ---
        // Convertimos el array asociativo en un array indexado simple para el @foreach de Blade
        $dataForView = [
            // Pasamos el array final. array_values() ignora las claves "Año-Mes".
            'consumptionDetails' => array_values($monthlyTemplate)
            // ... puedes pasar otros datos aquí
        ];

        return $dataForView;
    }

    public function makeMatrixConsumption($consumptionDetails = []): array
    {
        $numRows = 4;
        $numCols = 3;
        $tableData = [];

// Iteramos para crear las 4 filas de nuestra tabla
        for ($i = 0; $i < $numRows; $i++) {
            $row = [];
            // Iteramos para llenar las 3 columnas de cada fila
            for ($j = 0; $j < $numCols; $j++) {
                // La misma fórmula para leer verticalmente de la lista
                $index = $i + ($j * $numRows);

                if (isset($consumptionDetails[$index])) {
                    // Simplemente pasamos el item completo a la celda.
                    // No necesitamos formatear nada más, ya viene listo.
                    $row[] = $consumptionDetails[$index];
                } else {
                    // Placeholder por si la lista tuviera menos de 12 elementos
                    $row[] = ['title' => null, 'consumption' => null];
                }
            }
            $tableData[] = $row;
        }

        return $tableData;
    }

    public function getMonthSpanish(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
    }

    public function getBalanceHouse(int $house_id): array
    {
        $house = House::query()
            ->where('id', $house_id)
            ->with(['payments' => function ($query) {
                $query->select('id', 'house_id', 'amount', 'payment_date');
            }])
            ->with(['monthlyCharges' => function ($query) {
                $query->select('id', 'house_id', 'period_year', 'period_month', 'total_amount', 'status');
            }])
            ->firstOrFail();

        $payments = $house->payments->sum('amount'); // TOTAL PAYMENTS TO HOUSE_ID
        $amountCharges = $house->monthlyCharges
            ->sum('total_amount'); // TOTAL MONTHLY CHARGES TO HOUSE_ID

        return [
            'house_id' => $house_id,
            'amount_paid' => $payments,
            'opening_balance' => $house->opening_balance,
            'amount_due' => ($house->opening_balance + $amountCharges) - $payments,
            'house' => $house->toArray()
        ];

    }

    public function destroy(HouseMonthlyCharge $houseMonthlyCharge): JsonResponse
    {
        $disk = 'public';
        $filePath = $houseMonthlyCharge->pdf_path ?? null;

        try {
            if ($filePath && Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
            }
            $houseMonthlyCharge->delete();

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El registro fue eliminado correctamente.'
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error deleting quotation: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al intentar eliminar el registro.'], 500);
        }
    }

    public function download(HouseMonthlyCharge $houseMonthlyCharge): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Verifica que el archivo exista en el storage
        if (!Storage::disk('public')->exists($houseMonthlyCharge->pdf_path)) {
            // Loggea el error para depuración
            Log::error("Archivo no encontrado en storage: ID {$houseMonthlyCharge->id}, Path: {$houseMonthlyCharge->pdf_path}");
            return response()->json(['message' => 'File not found.'], 404);
        }

        // Retorna la descarga usando el nombre original del archivo
        return Storage::disk('public')->download($houseMonthlyCharge->pdf_path);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\BudgetScope;
use App\Models\AnnualBudget;
use App\Models\House;
use App\Models\HouseMonthlyCharge;
use App\Models\PaymentService;
use App\Models\Setting;
use App\Models\WebUser;
use App\Services\StatisticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Backtrace\Arguments\Reducers\DateTimeArgumentReducer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseMonthlyChargeController extends Controller
{
    /**
     * Almacena la fecha y hora de la instanciación del controlador.
     * @var \Illuminate\Support\Carbon
     */
    private $nowDate;
    private $previewMonthDate;
    const TYPE_HOUSE_ASSOCIATED = 'association_only';
    const TYPE_HOUSE_BOARD_ASSOCIATED = 'owners_board_with_association';
    const TYPE_HOUSE_BOARD = 'owners_board';
    const NRO_DEPARTMENT = 17;
    const FEE_JP_SARDINIA_ISLANDS = 334.66; // Cuota de la Asociación
    const FEET_IS_LOT = 150.00; // Cuota de la Asociación para lotes, si aplica
    const PRICE_BY_KWH = 0.625;

    private $statisticsService;
    private $settings;
    private int $houseId = 1;

    public function __construct()
    {
        $this->serviceStatistics = app(StatisticsService::class);
        $this->settings = Setting::query()
            ->where('group', 'general')
            ->pluck('value', 'key')
            ->toArray();

    }

    public function showPage(): View
    {
        $routes = [
            'base' => route('admin.house-monthly-charges.index'),
            'download' => route('admin.house-monthly-charges.download', ['houseMonthlyCharge' => 'PLACEHOLDER']),
        ];
        $meta = [
            'title' => 'Gestión Cobros Mensuales',
            'subtitle' => 'Gestión de presupuestos anuales de la asociación',
            'icon' => 'mdi mdi-account-group',
        ];

        return view('admin.house_monthly_charge.index', [
            'routes' => $routes,
            'meta' => $meta,
            'is_admin' => true,
        ]);
    }

    public function showPageByHouseId(House $house): View
    {
        $routes = [
            'base' => route('user.house.house-monthly-charges.index', ['house' => $house->id]),
            'download' => route('user.house-monthly-charges.download', ['houseMonthlyCharge' => 'PLACEHOLDER']),
        ];
        $meta = [
            'title' => 'Recibos de Mantenimiento',
            'subtitle' => 'Gestión de presupuestos anuales de la asociación',
            'icon' => 'mdi mdi-account-group',
        ];


        return view('user.houses.monthly_charge.index', [
            'routes' => $routes,
            'meta' => $meta,
            'house_id' => $house->id,
            'is_admin' => false,
        ]);
    }

    public function indexByHouseId(Request $request): JsonResponse
    {
        $webUser = Auth::guard('web_user')->user();

        if (!$webUser) {
            return response()->json(['message' => 'No autenticado.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $houseId = $request->input('house_id');
        $isRelated = $webUser->houses()->where('house_id', $houseId)->exists();

        if (!$isRelated) {
            return response()->json(['message' => 'No autorizado para acceder a este recurso.'], JsonResponse::HTTP_FORBIDDEN);
        }


        $query = $this->queryBase($request);

        return response()->json($query);
    }

    public function queryBase(Request $request): Collection
    {
        // Iniciar la consulta
        $query = HouseMonthlyCharge::query();

        // Cargar la relación con 'house' para obtener el nombre de la casa
        // y con 'details' si necesitas alguna información agregada de los detalles (opcional aquí)
        $query->with([
            'house:id,address,ownership_structure',
            'house.owners:id,name'
        ]); // Carga selectiva de columnas

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
        $monthlyCharges = $query->get();

        // Transformar los datos para la respuesta si es necesario
        // (ej. para formatear el nombre del mes)
        $monthlyCharges->transform(function ($charge) {
            // Crear un nombre de período legible
            // Carbon::createFromDate($charge->period_year, $charge->period_month, 1)->isoFormat('MMMM YYYY'); // Requiere locale configurado
            $charge->period_name = Carbon::create()->month($charge->period_month)->format('F') . ' ' . $charge->period_year; // "October 2023"

            // Puedes añadir más transformaciones aquí si es necesario
            // Por ejemplo, si quieres cargar selectivamente algunos detalles para un resumen
            // $charge->num_items = $charge->details()->count(); // Esto haría N+1 si no se optimiza

            return $charge;
        });
        return $monthlyCharges;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->queryBase($request);

        return response()->json($query);
    }

    public function preparedData(array $input): array
    {
        $house_id = $input['house_id'];
        $this->nowDate = Carbon::now()->subMonths(1)->startOfMonth();
        $previewMontDate = $this->nowDate->copy();
        $this->previewMonthDate = $previewMontDate->subMonth();

        $year = $input['year'] ?? $this->nowDate->year;
        $month = $this->nowDate->month;

        $preview = $input['is_preview'];

        $house = House::query()
            ->where('id', $house_id)
            ->with('owner')
            ->firstOrFail();
        $this->houseId = $house_id;
        $houseArray = $house->toArray();
        $typeHouse = $house->ownership_structure;
        $balanceHouse = $house->calculateBalance();

        $monthInput = (int)$month;
        $historyElectric = $this->getConsumptions($house_id);
        $matrix = $this->makeMatrixConsumption($historyElectric['consumptionDetails']);

        $settings = Setting::query()
            ->where('group', 'general')
            ->pluck('value', 'key')
            ->toArray();

        $signaturePath = $settings['signature_for_receipts_imagen'] ?? null;
        $tablaImagePath = $settings['annual_expense_statistics_imagen'] ?? null;
        if ($preview === "true") {
            $logoPath = asset('assets/images/logo.jpg');
            $tablaImagePath = asset('assets/images/statistical-table-v2.jpg'); // public/assets/images
            $signaturePath = asset('assets/images/firma-digital.jpg'); // public/assets/images
        } else {
            $logoPath = storage_path('app/public/file_paths/profile/nVcxTYTvFIndE6SVndfDMUTG6uFp5CPcCSFKhmFc.jpg');
            $tablaImagePath = storage_path('app/public/' . $tablaImagePath);
            $signaturePath = storage_path('app/public/' . $signaturePath);

        }

        Carbon::setLocale('es');
        $now = Carbon::now()->locale('es');
        $firstDay = $this->nowDate->copy();
        $firstDay->startOfMonth();
        $date_emitted = Str::ucfirst($firstDay->translatedFormat('F d, Y'));
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
            'signature_path' => $signaturePath,
            'title' => 'Asociación de Propietarios Islas de San Pedro',
            'tablaImagePath' => $tablaImagePath,
            'debt' => empty($balanceHouse['opening_balance']) ? 'Pendiente a Revisión' : number_format($balanceHouse['amount_due'], 2),
            'bank_name' => 'BANCO CREDITO DEL PERU (BCP)',
            'bank_account' => '194-72597403-0-08',
            'bank_account_cci' => '00219417259740300893',
            'bank_account_name' => 'Rudy David Huaranga Bolaños',
            'ruc_assoc_prop_isp' => 'RUC: 20525153861',

        ];
        $data['details'] = [];

        if ($typeHouse === self::TYPE_HOUSE_ASSOCIATED || $typeHouse === $this::TYPE_HOUSE_BOARD_ASSOCIATED) {
            $data['details'] = $this->getDetails($typeHouse, $houseArray);
        }

        $amount_of_month = array_sum(array_column($data['details'], 'amount'));
        $total_due = $balanceHouse['amount_due'] + $amount_of_month;
        $data = $data + [
                'amount_month' => $amount_of_month,
                'total_debt' => ($total_due > 0 ? $total_due : 0),
                'issued_date' => now()->format('d/m/Y'),
                'due_date' => now()->addDays(30)->format('d/m/Y'),
            ];
        $amount_of_month = number_format($amount_of_month, 2);

        if ($typeHouse === self::TYPE_HOUSE_ASSOCIATED) {
            if (!$houseArray['is_lot']) {
                $imageConsumption = $this->getImageConsumption($houseArray, $preview);
                $data = $data + [
                        'image_consumption' => $imageConsumption,
                    ];
            }

            $data = $data + [
                    'title_details_line_1' => 'RECIBO POR MANTENIMIENTO – ' . strtoupper($data['period_month']) . ' ' . $data['period_year'],
                    'title_details_line_2' => 'ASOCIACION DE PROPIETARIOS ISLAS DE SAN PEDRO',
                    'contact_email' => 'isp.asociacion@gmail.com',
                ];
        } else if ($typeHouse === self::TYPE_HOUSE_BOARD_ASSOCIATED) {
            $data = $data + [
                    'title_details_line_1' => 'RECIBO DE ASOCIADO',
                    'title_details_line_2' => 'ASOCIACION DE PROPIETARIOS ISLAS DE SAN PEDRO',
                    'contact_email' => 'isp.asociacion@gmail.com',
                ];
        } else if ($typeHouse === self::TYPE_HOUSE_BOARD) {
            dd('Esta función no está implementada para Junta de Propietarios');

            $data = $data + [
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
        } else {
            dd('La casa no tiene una estructura de propiedad válida.');
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
            $fileName = "{$data['associated']['name']}-{$data['house_id']}-{$month}-{$year}.pdf";
            $filePath = "file_paths/receipts/{$year}/{$month}/{$fileName}";

            // 4. Guardar el archivo en el disco de storage
            Storage::disk('public')->put($filePath, $pdf->output());

            // 5. Guardar el registro en la base de datos
            // updateOrCreate es perfecto para evitar duplicados
            $now = $this->nowDate;
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

    private function getCountHouses(int $houseId): int
    {
        $owner = House::findOwner($houseId);

        return $owner?->getHouseCount() ?? 0;
    }

    public function getDetails(string $type, array $house): array
    {
        if ($this::TYPE_HOUSE_BOARD === $type) {
            return [];
        }
        $fee_association_ISP = $this->getFeeAssociationISP();

        if ($type === self::TYPE_HOUSE_ASSOCIATED) { // Asociación I.S.P
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

    private function getImageConsumption(array $house, string $is_preview): string|null
    {
        $year = $this->previewMonthDate->year;
        $month = $this->previewMonthDate->month;
        $consumptionEnergy = $this->getLastMonthEnergyConsumptionPayment($house, $year, $month);
        $imageConsumptionBD = $consumptionEnergy['file_path_url'] ?? null;
        $imagePathConsumptionBD = $consumptionEnergy['file_path'] ?? null;
        if ($is_preview === "true") {
            $imageConsumption = $imageConsumptionBD;
        } else {
            $imageConsumption = $imagePathConsumptionBD ? storage_path('app/public/' . $imagePathConsumptionBD) : null;
        }

        return $imageConsumption;
    }

    private function getDepartmentEnergyDetails(array $house, float $fee_association_ISP): array
    {

        $pricePerKw = (float)($this->settings['price_per_kw'] ?? 0);
        if ($pricePerKw <= 0) {
            $pricePerKw = self::PRICE_BY_KWH; // Usando tu constante
        }

        $year = $this->previewMonthDate->year;
        $month = $this->previewMonthDate->month;

        $consumptionEnergy = $this->getLastMonthEnergyConsumptionPayment($house, $year, $month);
        $amountElectric = 0;
        if ($consumptionEnergy) {
            // CONVERTIMOS AMBOS VALORES A FLOAT ANTES DE MULTIPLICAR
            $consumption = (float)$consumptionEnergy['consumption_calculated'] ?? 0;
            $amountElectric = $consumption * $pricePerKw;
        }


        $totalBuildingBudget = $this->getTotalBuildingBudget();
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
                'amount' => self::FEE_JP_SARDINIA_ISLANDS - $totalBuildingBudget - $fee_association_ISP + $house['cost_jp']
            ]
        ];
    }

    private function getLastMonthEnergyConsumptionPayment(array $house, int $year, int $month): array
    {
        $payment = PaymentService::query()
            ->where([
                ['service_id', 1],
                ['house_id', $house['id']],
            ])
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date',  $month)
            ->latest('payment_date')
            ->first();

        if (!$payment) {
            Log::warning("No se encontró un pago de energía para la casa ID {$house['id']} en el mes anterior.");
            return [];
        }

        return $payment->toArray();
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
        $associatedUsersCount = $this->serviceStatistics->getAssociatedUsersCount();
        $numberHouses = $this->getCountHouses($this->houseId);

        if ($associatedUsersCount === 0 || $numberHouses === 0) {
            return [];
        }

        $annualBudgets = AnnualBudget::query()
            ->whereHas('budgetType', function ($query) {
                $query->where('budget_scope', BudgetScope::ASSOCIATION->value);
            })
            ->with('budgetType')
            ->where('year', Carbon::now()->year)
            ->get();

        return $annualBudgets->map(function ($budget) use ($associatedUsersCount, $numberHouses) {
            return [
                'title' => $budget->budgetType->name,
                'amount' => ($budget->amount / 12) / $associatedUsersCount / $numberHouses,
            ];
        })->toArray();

    }

    public function getBuildingBudgetList(): array
    {
        $nroDepartments = $this->serviceStatistics->getNroDepartments();
        if ($nroDepartments === 0) {
            return [];
        }
        $annualBudgets = AnnualBudget::query()
            ->whereHas('budgetType', function ($query) {
                $query->where('budget_scope', BudgetScope::BUILDING->value);
            })
            ->with('budgetType')
            ->where('year', Carbon::now()->year)
            ->get();

        return $annualBudgets->map(function ($budget) use ($nroDepartments) {
            return [
                'title' => $budget->budgetType->name,
                'amount' => ($budget->amount / 12) / $nroDepartments,
            ];
        })->toArray();

    }

    public function getTotalBuildingBudget(): float
    {
        $result = $this->getBuildingBudgetList();
        return array_sum(array_column($result, 'amount'));
    }

    private function addPompeyaFundIfApplies(array $details, array $house): array
    {
        if ($house['is_department']) {
            $totalBuildingBudget = $this->getTotalBuildingBudget();
            array_unshift($details, [
                'title' => 'Fondos del Edificio Pompeya',
                'amount' => $totalBuildingBudget
            ]);
        }

        return $details;
    }

    public function getConsumptions(int $house_id): array
    {
        // --- 1. Definir el Rango de Fechas (Esta parte se mantiene igual) ---
        $startDate = Carbon::createFromDate(now()->year - 1, 12, 1)->startOfDay();
        $endDate = Carbon::createFromDate(now()->year, 11, 1)->endOfMonth()->endOfDay();

        // --- 2. Crear el Array "Plantilla" (Esta parte se mantiene igual) ---
        $monthlyTemplate = [];
        $dateIterator = $startDate->copy();
        $spanishMonths = $this->getMonthSpanish(); // Asumo que este método existe

        while ($dateIterator->lessThanOrEqualTo($endDate)) {
            $year = $dateIterator->year;
            $month = $dateIterator->month;
            $key = "$year-$month";

            $monthlyTemplate[$key] = [
                'year' => $year,
                'month_name' => $spanishMonths[$month],
                'title' => $spanishMonths[$month] . ' ' . $year,
                'consumption' => 'N/A'
            ];

            $dateIterator->addMonth();
        }

        // --- 3. Consultar y Procesar con Eloquent y Colecciones (AQUÍ ESTÁ EL CAMBIO) ---

        // PASO 3.1: Obtener el último pago ANTES del período.
        // Esto es CRUCIAL para que el accesor pueda calcular el consumo del primer mes (Diciembre).
        $paymentBeforePeriod = PaymentService::where('house_id', $house_id)
            ->where('service_id', 1)
            ->where('payment_date', '<', $startDate)
            ->orderBy('payment_date', 'desc')
            ->first();

        // PASO 3.2: Obtener los pagos DENTRO del período solicitado.
        $paymentsInPeriod = PaymentService::where('house_id', $house_id)
            ->where('service_id', 1)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'asc')
            ->get();

        // PASO 3.3: Combinar ambos resultados en una única colección ordenada.
        $allRelevantPayments = new Collection();
        if ($paymentBeforePeriod) {
            $allRelevantPayments->push($paymentBeforePeriod);
        }
        $allRelevantPayments = $allRelevantPayments->concat($paymentsInPeriod);

        // PASO 3.4: Mapear la colección para invocar el accesor y obtener los datos.
        // Aquí es donde la magia ocurre. Al acceder a ->consumption_calculated, Eloquent lo calcula por nosotros.
        $dbConsumptions = $allRelevantPayments->map(function (PaymentService $payment) {
            return (object)[ // Creamos un objeto para imitar la salida original de la BD
                'year' => $payment->payment_date->year,
                'month' => $payment->payment_date->month,
                // ¡AQUÍ SE USA TU ACCESOR!
                'consumption' => $payment->quantity,
            ];
        });

        // --- 4. Combinar los Datos (Adaptamos ligeramente esta parte) ---
        // Recorremos los resultados calculados y actualizamos nuestra plantilla.
        foreach ($dbConsumptions as $consumption) {
            $key = "{$consumption->year}-{$consumption->month}";
            // Verificamos si la clave existe en nuestra plantilla para ignorar el registro "anterior"
            if (isset($monthlyTemplate[$key])) {
                // Tu accesor se llama 'consumption_calculated', pero lo hemos renombrado a 'consumption' en el paso 3.4
                $monthlyTemplate[$key]['consumption'] = $consumption->consumption;
            }
        }

        // --- 5. Preparar Datos para la Vista (Esta parte se mantiene igual) ---
        $dataForView = [
            'consumptionDetails' => array_values($monthlyTemplate)
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

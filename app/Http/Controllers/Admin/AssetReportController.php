<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Setting;
use App\Services\FinancialReportService;
use App\Services\SharedViewDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AssetReportController extends Controller
{
    private SharedViewDataService $sharedViewDataService;
    /**
     * @var mixed[]
     */
    private array $settings;
    private string $fileNameBlade = 'pdf.assets';


    public function __construct(SharedViewDataService $sharedViewDataService)
    {
        $this->sharedViewDataService = $sharedViewDataService;
        $this->settings = Setting::query()
            ->where('group', 'general')
            ->pluck('value', 'key')
            ->toArray();
    }

    public function showListPage(): View
    {
        return view('admin.reports.assets.index');
    }

    public function previewPdf(Request $request): view
    {
        $data = $this->prepareData($request);
        $data['data'] = $data;
        $data['attributes'] = $this->getAttributeToReport($request, true);

        $viewToLoad = 'pdf.assets';
        return view($viewToLoad, $data);

    }

    public function downloadPdf(Request $request): \Illuminate\Http\Response
    {
        $data['data'] = $this->prepareData($request);
        $data['attributes'] = $this->getAttributeToReport($request, false);
        // Cargamos la misma vista Blade en el generador de PDF
        $pdf = PDF::loadView($this->fileNameBlade, $data);

        // Descargamos el archivo
        return $pdf->download('assets-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getAssets()
    {
        return Expense::query()
            ->where('is_asset', true)
            ->orderBy('expense_date', 'asc')->get();
    }

    private function prepareData(Request $request): array
    {
        $assets = $this->getAssets();
        $assets = $assets->map(function ($item) {
            $item->expense_date_format = ucfirst(Carbon::parse($item->expense_date)->translatedFormat('F Y'));
            return $item;
        });
        // Agrupamos la colección principal por Año
        $groupedByYear = $assets->groupBy(function ($item) {
            return Carbon::parse($item->expense_date)->format('Y');
        });

        $data = [];
        // Recorremos cada año para armar la estructura
        foreach ($groupedByYear as $year => $items) {
            // FILTRAR: Separar Activos Principales de Suministros.
            $assetsmain = $items->filter(function ($item) {
                return $item->asset_type === 'asset';
            });

            $assetsSupplies = $items->filter(function ($item) {
                return $item->asset_type === 'supply';
            });

            // CALCULAMOS LOS SUBTOTALES
            $subAmountMain = $assetsmain->sum('amount');
            $subMarkerValueMain = $assetsmain->sum('market_value');

            $subAmountSupplies = $assetsSupplies->sum('amount');
            $subMarkerValueSupplies = $assetsSupplies->sum('market_value');

            $data[$year] = [
                'year' => $year,
                'assets' => [
                    'items' => $assetsmain->values()->toArray(),
                    'subtotal_amount' => $subAmountMain,
                    'subtotal_marker_value' => $subMarkerValueMain,
                ],
                'supplies' => [
                    'items' => $assetsSupplies->values()->toArray(),
                    'subtotal_amount' => $subAmountSupplies,
                    'subtotal_marker_value' => $subMarkerValueSupplies,
                ],
                'sub_total_anho' => [
                    'total_amount' => $subAmountMain + $subAmountSupplies,
                    'total_marker_value' => $subMarkerValueMain + $subMarkerValueSupplies,
                ]
            ];
        }

        //krsort() en PHP sirve para ordenar un arreglo asociativo por sus claves (keys)
        // en orden descendente.
        krsort($data);

        return $data;
    }

    private function getAttributeToReport(Request $request, $isPreview = false): array
    {
        $logoPathDB = $this->settings['logo_for_receipts_imagen'] ?? null;
        $logoPath = $this->sharedViewDataService->get($logoPathDB, $isPreview);
        $signaturePathDB = $this->settings['signature_for_receipts_imagen'] ?? null;
        $signaturePath = $this->sharedViewDataService->get($signaturePathDB, $isPreview);

        return [
            'logo_path' => $logoPath,
            'site_name' => strtoupper($this->settings['site_title']),
            'signature_path' => $signaturePath,
            'name_president' => $this->settings['name_president'],
            'is_preview' => $isPreview,
        ];
    }
}

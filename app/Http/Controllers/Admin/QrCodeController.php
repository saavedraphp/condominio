<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrCodeRequest;
use App\Models\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class QrCodeController extends Controller
{
    public function showListPage(): View
    {
        $routes = [
            'base' => route('admin.qr-codes.index'),
        ];
        return view('admin.qr_codes.index', [
            'routes' => $routes,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = QrCode::query()->get();
            return response()->json([
                'success' => true,
                'data' => $data,
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error al obtener los codigos QR ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar la solicitud',
            ], 500);
        }
    }

    public function store(QrCodeRequest $request):JsonResponse
    {
        $validated = $request->validated();

        try {
            $qrCode = QrCode::create($validated);
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación sealizó con éxito.',
                'data' => $qrCode,
            ], 201);
        } catch (\exception $e) {
            Log::error('Error al adicionar el código QR' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ócurrio un error al intentar insertar el registro : ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(QrCodeRequest $request, QrCode $qrCode): JsonResponse
    {
        $validated = $request->validated();

        try {
            $qrCode->update($validated);
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación se realizó con éxito.',
                'data' => $qrCode,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar el código QR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ócurrio un error al intentar actualizar el registro: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(QrCode $qrCode): JsonResponse
    {
        try {
            $qrCode->delete();
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación se realizó con éxito.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar el código QR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ócurrio un error al intentar eliminar el registro: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getQr(QrCode $qrCode): JsonResponse
    {
        // Autorizamos que el usuario actual pueda ver este pase
        //$this->authorize('view', $visitPass);
        $qrCodeImage = QrCodeGenerator::format('png')
            ->size(250) // Size in pixels
            ->margin(1) // Margin around the QR code
            ->generate($qrCode->qr_content);

        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage);
        // Devolvemos una respuesta JSON bien estructurada
        return response()->json([
            'title' => $qrCode->title,
            'description' => $qrCode->description,
            'code' => $qrCode->code,
            'type' => $qrCode->type,
            'qr_code' => $qrCodeBase64,
        ]);
    }

    public function downloadPdf(QrCode $qrCode)
    {
        //$this->authorize('view', $visitPass);

        // Generamos el QR como una imagen PNG codificada en Base64 para incrustarla en el PDF
        $qrCodeZone = base64_encode(QrCodeGenerator::format('png')->size(250)->generate($qrCode->qrContent));
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);
        $data = [
            'data' => $qrCode,
            'qrCode' => $qrCodeZone
        ];

        // Cargamos la vista de Blade para el PDF
        $pdf = PDF::loadView('pdf.qr_zones', $data);

        // Descargamos el archivo
        return $pdf->download('Qr-zona' . $qrCode->title . '.pdf');
    }

    public function downloadImage(QrCode $qrCode)
    {
        $jsonContentForQr = $qrCode->qr_content;

        // Genera el QR con alta calidad
        $qrImage = QrCodeGenerator::format('png')
            ->size(500) // Genera una imagen grande y de alta calidad
            ->errorCorrection('Q')
            ->margin(4)
            ->backgroundColor(255, 255, 255)
            ->generate($jsonContentForQr);

        // Prepara el nombre del archivo
        $fileName = Str::slug($qrCode->title . '-' . $qrCode->code) . '.png';

        // Devuelve la imagen como una respuesta de descarga
        return response($qrImage)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}

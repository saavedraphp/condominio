<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\VisitPassRequest;
use App\Models\House;
use App\Models\VisitPass;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VisitPassController extends Controller
{
    public function showPage(House $house): View
    {
        $webUser = Auth::guard('web_user')->user();
        $routes = [
            'base' => route('user.house.visit-passes.index', ['house' => $house->id]),
        ];
        $data = [
            'webUser' => $webUser,
            'house' => $house,
            'routes' => $routes,
        ];
        return view('user.visit_passes.index', $data);
    }

    public function index(House $house): JsonResponse
    {
        try {

            $data = Auth::guard('web_user')->user()->visitPasses()
                ->with('house', 'members')
                ->orderBy('expires_at','desc')->get();

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos'], 500);
        }
    }

    public function store(House $house, VisitPassRequest $request): JsonResponse
    {
        // Usamos una transacción por si falla la creación de integrantes.

        $validated = $request->validated();
        try {
            $startsAt = Carbon::parse($validated['starts_at'])->startOfDay();
            $expiresAt = Carbon::parse($validated['expires_at'])->endOfDay();

            // Creamos el pase usando la relación para asignar el creator_id y creator_type automáticamente
            $visitPass = Auth::user()->visitPasses()->create([
                'title' => $validated['title'],
                'details' => $validated['details'],
                'house_id' => $validated['house_id'],
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'access_code' => $this->generateUniqueAccessCode(),
            ]);
            // Si se enviaron integrantes, los creamos
            if (!empty($validated['members'])) {
                foreach ($validated['members'] as $member) {
                    $visitPass->members()->create($member);
                }
            }
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación se realizó con éxito.',
                'data' => $visitPass,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos'], 500);
        }

    }

    public function update(VisitPassRequest $request, House $house, VisitPass $visitPass): JsonResponse
    {

        $validated = $request->validated();
        try {
            $startsAt = Carbon::parse($validated['starts_at'])->startOfDay();
            $expiresAt = Carbon::parse($validated['expires_at'])->endOfDay();

            $visitPass->update([
                'title' => $validated['title'],
                'details' => $validated['details'],
                'house_id' => $validated['house_id'],
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
            ]);
            // Si se enviaron integrantes, los creamos
            if (!empty($validated['members'])) {
                // Eliminamos los miembros existentes para reemplazarlos por los nuevos
                $visitPass->members()->delete();
                foreach ($validated['members'] as $member) {
                    $visitPass->members()->create($member);
                }
            }
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación se realizó con éxito.',
                'data' => $visitPass,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al intentar obtener los datos' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar obtener los datos'], 500);
        }

    }

    public function destroy(House $house, VisitPass $visitPass): JsonResponse
    {
        try {
            $visitPass->delete();
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! La operación se realizó con éxito.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al intentar eliminar el pase de visita' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Ócurrio un error al intentar eliminar el pase de visita'], 500);
        }

    }

    private function generateUniqueAccessCode(): string
    {
        do {
            $code = Str::upper(
                Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4)
            );
        } while (VisitPass::where('access_code', $code)->exists());

        return $code;
    }

    public function getVirtualPassData(VisitPass $visitPass): JsonResponse
    {
        // Autorizamos que el usuario actual pueda ver este pase
        //$this->authorize('view', $visitPass);

        // Cargamos las relaciones necesarias
        $visitPass->load('creator', 'house');
        $qrCodeImage = QrCode::format('png')
            ->size(250) // Size in pixels
            ->margin(1) // Margin around the QR code
            ->generate($visitPass->access_code);

        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage);
        // Devolvemos una respuesta JSON bien estructurada
        return response()->json([
            'title' => $visitPass->title,
            'details' => $visitPass->details,
            'creator_name' => $visitPass->creator->name, // Nombre del Propietario
            'house_address' => $visitPass->house->address, // Dirección de la casa
            'starts_at' => $visitPass->starts_at,
            'expires_at' => $visitPass->expires_at,
            'access_code' => $visitPass->access_code,
            'qr_code' => $qrCodeBase64,
        ]);
    }

    public function downloadPdf(VisitPass $visitPass)
    {
        //$this->authorize('view', $visitPass);
        $visitPass->load('creator', 'house');

        // Generamos el QR como una imagen PNG codificada en Base64 para incrustarla en el PDF
        $qrCode = base64_encode(QrCode::format('png')->size(250)->generate($visitPass->access_code));
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);
        $startDateFormatted = $visitPass->starts_at;
        $endDateFormatted = $visitPass->expires_at;

        $data = [
            'pass' => $visitPass,
            'startDate' => $startDateFormatted,
            'endDate' => $endDateFormatted,
            'qrCode' => $qrCode
        ];

        // Cargamos la vista de Blade para el PDF
        $pdf = PDF::loadView('pdf.virtual_pass', $data);

        // Descargamos el archivo
        return $pdf->download('pase-visita-' . $visitPass->access_code . '.pdf');
    }

}

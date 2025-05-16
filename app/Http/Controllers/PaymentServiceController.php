<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentServiceRequest;
use App\Models\House;
use App\Models\PaymentService;
use App\Models\WebUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentServiceController extends Controller
{
    public function showPage(House $house): View
    {
        $webUser = Auth::guard('web_user')->user();
        return view('user.houses.electricity_consumption', [
                'webUserId' => $webUser->id,
                'houseId' => $house->id,
                'isAdmin' => false,
                'typeServiceId' => 1, //LUZ
            ]
        );
    }

    public function showPageAll(): View
    {
        return view('user.houses.electricity_consumption', [
                'typeServiceId' => 1,
                'isAdmin' => true,
            ]
        );
    }

    public function showPageAllWater(): View
    {
        return view('user.houses.water_consumption', [
                'typeServiceId' => 0,
                'isAdmin' => true,
            ]
        );
    }

    public function showPageWater(House $house): View
    {
        $webUser = Auth::guard('web_user')->user();
        return view('user.houses.water_consumption', [
                'webUserId' => $webUser->id,
                'houseId' => $house->id,
                'isAdmin' => false,
                'typeServiceId' => 0,
            ]
        );
    }

    public function index(Request $request, House $house): JsonResponse
    {
        $typeService = $request->get('type_service');
        try {
            $servicePayments = PaymentService::query()
                ->where('house_id', $house->id)
                ->where('service_id', $typeService)
                ->get();
            return response()->json($servicePayments);

        } catch (\Exception $e) {
            $errorMessage = 'Ocurrio un error al intentar obtener la los datos. ';
            Log::error($errorMessage . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $errorMessage . $e->getMessage()
            ], 500);
        }
    }

    public function indexAll(Request $request): JsonResponse
    {
        $typeService = $request->get('type_service');
        try {
            $servicePayments = PaymentService::query()
                ->where('service_id', $typeService)
                ->with('house')
                ->orderBy('payment_date', 'desc')
                ->get();
            return response()->json($servicePayments);
        } catch (\Exception $e) {
            $errorMessage = 'Ocurrio un error al intentar obtener la los datos. ';
            Log::error($errorMessage . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $errorMessage . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $filePath = null;
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
                $file = $request->file('file_path');
                $filePath = $file->store('file_paths/consumption', 'public');

                if (!$filePath) {
                    return response()->json(['error' => 'No se pudo guardar el archivo.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['error' => 'Archivo  inválido o no encontrado.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $house = House::find($request->get('house_id'));
            $paymentService = PaymentService::create([
                'house_id' => $house->id,
                'service_id' => $request->get('service_id'),
                'quantity' => $request->get('quantity'),
                'observations' => $request->get('observations'),
                'replace' => $request->get('replace'),
                'payment_date' => $request->get('payment_date'),
                'file_path' => $filePath
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El consumo fue registrado exitosamente.',
                'data' => $paymentService,
            ], JsonResponse::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Ocurrio un error al registrar el consumo: ' . $e);
            return response()->json([
                'success' => false,
                'message' => 'Ocurrio un error al registrar el consumo'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(PaymentServiceRequest $request, PaymentService $paymentService): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $dataToUpdate = [
                'house_id' => $validatedData['house_id'],
                'service_id' => $validatedData['service_id'],
                'quantity' => $request->get('quantity'),
                'observations' => $validatedData['observations'],
                'replace' => $request->get('replace'),
                'payment_date' => $validatedData['payment_date'],
            ];

            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {

                if ($paymentService->file_path && Storage::disk('public')->exists($paymentService->file_path)) {
                    Storage::disk('public')->delete($paymentService->file_path);
                }

                $file = $request->file('file_path');
                $filePath = $file->store('file_paths/consumption', 'public');

                $dataToUpdate['file_path'] = $filePath;
            }

            $paymentService->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El registro fue actualizado exitosamente.',
                'data' => $paymentService,
            ], JsonResponse::HTTP_OK);

        } catch (\exception $e) {
            $errorMessage = 'Error al actualizar el registro id: ' . $paymentService?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar actualizar el registro.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(PaymentService $paymentService): JsonResponse
    {
        $filePath = $paymentService->file_path;

        try {

            DB::transaction(function () use ($paymentService, $filePath) {
                $disk = 'public';
                if ($filePath && Storage::disk($disk)->exists($filePath)) {
                    Storage::disk($disk)->delete($filePath);
                }
                $paymentService->delete();

            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! registro fue eliminado correctamente.',
                JsonResponse::HTTP_OK
            ]);

        } catch (\exception $e) {
            $errorMessage = 'Error eliminando el registro id: ' . $paymentService?->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el documento.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}

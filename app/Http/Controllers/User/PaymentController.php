<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\House;
use App\Models\HousePayment;
use App\Models\WebUser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Throwable;


class PaymentController extends Controller
{
    public function showPage(House $house): View
    {
        $webUser = Auth::guard('web_user')->user();
        return view('user.payment_list', ['webUser' => $webUser, 'house' => $house]);
    }

    public function index(House $house): JsonResponse
    {
        try {
            $userId = Auth::guard('web_user')->id();
            $user = WebUser::findOrFail($userId);
            $paymentsMade = $user->paymentsMade()->get();
            $firstPayment = $paymentsMade->first();
            return response()->json($paymentsMade);
        } catch (\Exception $e) {
            $errorMessage = 'Error al intentar obtener las los pagos. ';
            Log::error($errorMessage . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $errorMessage . $e->getMessage()
            ], 500);
        }
    }

    public function downloadPayment(HousePayment $payment)
    {
        // --- Autorización ---
        // Asegurarse que el usuario autenticado es dueño del pago
        // Puedes usar Gate::authorize o una verificación directa:
        if ($payment->web_user_id !== Auth::guard('web_user')->id()) {
            return response()->json(['message' => 'No autorizado para descargar este archivo.'], Response::HTTP_FORBIDDEN);
        }


        if (empty($payment->file_path)) {
            return response()->json(['message' => 'Este pago no tiene un archivo de comprobante asociado.'], Response::HTTP_NOT_FOUND);
        }

        $diskName = config('filesystems.default_proof_disk', 'public');


        try {
            if (!Storage::disk($diskName)->exists($payment->file_path)) {
                return response()->json(['message' => 'El archivo del comprobante no se encuentra en el servidor.'], Response::HTTP_NOT_FOUND);
            }

            $fileName = $payment->file_name ?? basename($payment->file_path);
            return Storage::disk($diskName)->download($payment->file_path, $fileName);

        } catch (FileNotFoundException $e) {
            report($e);
            return response()->json(['message' => 'El archivo del comprobante no se pudo encontrar (error interno).'], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'No se pudo iniciar la descarga del archivo debido a un error interno.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadYearlyPdf(int $year)
    {
        // --- Validación básica del año ---
        if ($year < 1900 || $year > Carbon::now()->year + 5) { // Rango razonable
            abort(Response::HTTP_BAD_REQUEST, 'Año inválido.');
        }

        // --- Obtener Pagos del Año para el Usuario Autenticado ---
        $user = Auth::guard('web_user')->user();
        $payments = $user->paymentsMade() // Asume que tienes esta relación en tu modelo User
        ->whereYear('payment_date', $year)
            ->orderBy('payment_date', 'asc') // Ordenar por fecha
            ->get();

        // --- Verificar si hay pagos ---
        if ($payments->isEmpty()) {
            // Puedes retornar un PDF vacío, un mensaje, o redirigir
            return back()->with('warning', 'No se encontraron pagos registrados para el año ' . $year);
            // O generar un PDF con un mensaje:
            // $pdf = Pdf::loadHTML("<h1>No hay pagos para el año {$year}</h1>");
            // return $pdf->download("Comprobantes-{$year}-Vacio.pdf");
        }

        // --- Preparar datos para la vista del PDF ---
        $data = [
            'year' => $year,
            'payments' => $payments,
            'userName' => $user->name, // Opcional: mostrar nombre del usuario
            'generationDate' => Carbon::now()->format('d/m/Y H:i'), // Opcional
        ];

        // --- Generar el PDF ---
        try {
            // Cargar la vista Blade que diseñará el PDF
            // Asegúrate de crear esta vista en: resources/views/pdfs/payments_yearly.blade.php
            $pdf = Pdf::loadView('pdfs.payments_yearly', $data);

            // Opciones (opcional):
            $pdf->setPaper('a4', 'portrait'); // 'portrait' o 'landscape'

            // Definir nombre del archivo PDF
            $pdfFileName = "Comprobantes-{$user->id}-{$year}.pdf"; // Nombre único

            // Descargar el PDF
            return $pdf->download($pdfFileName);

        } catch (\Exception $e) {
            // Log del error $e->getMessage() y $e->getTraceAsString()
            report($e); // Usar el helper report() de Laravel
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Ocurrió un error al generar el PDF resumen.');
        }
    }

    public function store(PaymentRequest $request, House $house): JsonResponse
    {
        $webUserId = Auth::guard('web_user')->id();
        try {
            $validatedData = $request->validated();

            // --- 2. Manejo del Archivo ---
            $filePath = null;
            if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
                $file = $request->file('file_path');

                // Guarda el archivo en storage/app/public/file_paths
                // Laravel generará un nombre único para evitar colisiones.
                // Asegúrate de haber ejecutado `php artisan storage:link`
                $filePath = $file->store('file_paths', 'public');

                if (!$filePath) {
                    return response()->json(['error' => 'No se pudo guardar el archivo comprobante.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['error' => 'Archivo comprobante inválido o no encontrado.'], JsonResponse::HTTP_BAD_REQUEST);
            }


            $payment = HousePayment::create([
                'title' => $validatedData['title'],
                'payment_date' => now(),
                'amount' => $validatedData['amount'],
                'file_path' => $filePath,
                'house_id' => $house->id,
                'web_user_id' => $webUserId,
                'status' => 'approved',
            ]);


            // --- 4.  'proof_file_url' => Storage::disk('public')->url($filePath) // URL pública si storage:link está hecho
            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El Pago y su archivo fue registrado exitosamente.',
                'data' => $payment,
            ], JsonResponse::HTTP_CREATED);


        } catch (\exception $e) {
            $messageError = 'Ócurrio un error al intentar agregar un pago. ';
            Log::error($messageError . $e->getMessage());
            return response()->json(['success' => false, 'message' => $messageError . $e->getMessage()], 500);
        }
    }

    public function destroy(House $house, HousePayment $payment): JsonResponse
    {
        if ($payment->house_id !== $house->id) {
            Log::warning("Intento de borrado denegado: Pago ID {$payment->id} no pertenece a Casa ID {$house->id}.");
            return response()->json(['success' => false, 'message' => 'Acción no autorizada o recurso no encontrado.'], 403);
        }

        $filePath = $payment->file_path;
        $disk = 'public';

        try {
            DB::transaction(function () use ($payment, $disk, $filePath) {
                if ($filePath && Storage::disk($disk)->exists($filePath)) {
                    Storage::disk($disk)->delete($filePath);
                }
                $payment->delete();

            });

            return response()->json([
                'success' => true,
                'message' => '¡Excelente! El registro y su archivo asociado se han eliminado correctamente.',
                JsonResponse::HTTP_CREATED
            ]);

        } catch (Throwable $e) {
            $errorMessage = 'Error eliminando el registro id: ' . optional($payment)->id;
            Log::error($errorMessage . ': ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al intentar eliminar el registro.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}

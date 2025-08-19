<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\ActivityLog;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatrolService
{
    /**
     * Registra una marcación de patrullaje validando un código QR y creando un log de actividad.
     * Este método está diseñado para ser llamado desde un controlador.
     *
     * @param string $qrCodeIdentifier El 'code' del QR escaneado (ej: "ZONE-A-ROOF").
     * @param User $guard El usuario autenticado que realiza el escaneo.
     * @return array Un arreglo que contiene el resultado de la operación.
     */
    public function logCheckpoint(string $qrCodeIdentifier, User $guard): array
    {
        try {
            // Usamos una transacción para asegurar la integridad de los datos. Si algún paso falla, todo se revierte.
            $result = DB::transaction(function () use ($qrCodeIdentifier, $guard) {

                // 1. Buscar el código QR para la ubicación especificada.
                $qrCode = QrCode::where('code', $qrCodeIdentifier)->first();

                // 2. Manejar caso: Código QR no encontrado en la base de datos.
                if (!$qrCode) {
                    return [
                        'success' => false,
                        'status'  => 'NOT_FOUND',
                        'message' => 'El código de la ubicación escaneada no existe.',
                        'http_code' => 404 // Not Found
                    ];
                }

                // 3. (Opcional pero recomendado) Validar el tipo de código QR.
                if ($qrCode->type !== 'PATROL_CHECKPOINT') {
                    return [
                        'success' => false,
                        'status'  => 'INVALID_TYPE',
                        'message' => 'Este código QR no es válido para marcaciones de patrullaje.',
                        'http_code' => 422 // Unprocessable Entity
                    ];
                }

                // 4. Crear la entrada en el log de actividad. Este es el resultado exitoso.
                $log = ActivityLog::create([
                    'user_id'       => $guard->id,
                    'qr_code_id'    => $qrCode->id,
                    'code'          => $qrCode->code, // Guardamos una copia del código al momento del escaneo.
                    'status'        => ActivityStatus::OK, // Estado por defecto en un escaneo exitoso.
                ]);

                // 5. Estructurar la respuesta de éxito para el frontend.
                // Cargamos las relaciones para incluir detalles en la respuesta.
                $log->load(['user', 'qrCode']);

                return [
                    'success' => true,
                    'status' => 'SUCCESS',
                    'message' => 'Marcación registrada correctamente.',
                    'http_code' => 201, // Created
                    'data' => [
                        'log_id' => $log->id, // Importante para poder añadir observaciones después.
                        'user' => [
                            'name' => $log->user->name,
                        ],
                        'qr_code' => [
                            'title' => $log->qrCode->title,
                            'description' => $log->qrCode->description,
                        ],
                        'created_at' => $log->created_at->toIso8601String(),
                        'remarks' => $log->remarks,
                    ]
                ];
            });

            return $result;

        } catch (\Throwable $e) {
            // Capturar cualquier excepción inesperada durante la transacción.
            Log::error("Error en PatrolService::logCheckpoint: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Devolver una respuesta de error genérica del servidor.
            return [
                'success' => false,
                'status' => 'SERVER_ERROR',
                'message' => 'Ocurrió un error inesperado en el servidor. Por favor, contacte a soporte.',
                'http_code' => 500
            ];
        }
    }
}

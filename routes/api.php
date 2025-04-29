<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentController;


Route::middleware('auth:sanctum')->group(function () { // Descomenta si usas Sanctum

Route::get('/documents', [DocumentController::class, 'index']);
Route::get('/documents/{document}', [DocumentController::class, 'show'])
    ->where('document', '[0-9]+'); // Asegura que el ID sea numérico

// Ruta nombrada para la descarga segura
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download') // Nombre de la ruta usado en el Modelo
    ->where('document', '[0-9]+');

});

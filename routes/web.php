<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\UploadController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\WebController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\CommandsController;

// Endpoints equivalentes al backend Node
Route::post('/upload', [UploadController::class, 'upload']);
Route::get('/files/imei/{imei}', [UploadController::class, 'filesByImei']);
// Servir archivos desde /videos (solo lectura)
Route::get('/videos/{path}', [MediaController::class, 'show'])->where('path', '.*');
// Conversión de TS a MP4
Route::get('/video/convert', [MediaController::class, 'convert']);
// Vistas básicas
Route::get('/', [WebController::class, 'home']);
Route::get('/device/{imei}', [WebController::class, 'device']);
Route::get('/historico', [HistoricoController::class, 'index']);
Route::get('/comandos', [CommandsController::class, 'index']);

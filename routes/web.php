<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntradaController;

Route::get('/entradas/comprar', [EntradaController::class, 'crear'])->name('entradas.comprar');

Route::post('/entradas/procesar', [EntradaController::class, 'procesar'])->name('entradas.procesar');

Route::get('/', function () {
    return view('welcome');
});

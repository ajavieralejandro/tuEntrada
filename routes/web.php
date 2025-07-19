    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\EntradaController;

    Route::get('/entradas/comprar', [EntradaController::class, 'crear'])->name('entradas.comprar');

    Route::post('/entradas/procesar', [EntradaController::class, 'procesar'])->name('entradas.procesar');


    Route::get('/entradas/success', [EntradaController::class, 'success'])->name('entradas.success');

    Route::get('/entradas/failure', [EntradaController::class, 'failure'])->name('entradas.failure');

    Route::get('/entradas/pending', [EntradaController::class, 'pending'])->name('entradas.pending');

    Route::get('/', function () {
        return view('welcome');
    });

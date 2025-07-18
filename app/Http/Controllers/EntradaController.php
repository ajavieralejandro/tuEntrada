<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EntradaController extends Controller
{
    public function crear()
    {
        return view('entradas.compra');
    }

    public function procesar(Request $request)
    {
        // Por ahora solo mostramos los datos recibidos (para pruebas)
        return response()->json($request->all());
    }
}


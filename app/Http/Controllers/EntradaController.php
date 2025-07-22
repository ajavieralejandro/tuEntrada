<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use App\Services\BrevoMailService;

class EntradaController extends Controller
{
    public function procesar(Request $request)
{
    $accessToken = config('services.mercadopago.access_token');
    MercadoPagoConfig::setAccessToken($accessToken);

    $client = new PreferenceClient();

    $cantidad = (int) $request->input('cantidad'); // ✅ Cast correcto a entero

    try {
    $preference = $client->create([
        "items" => [
            [
                "title" => "Entrada Evento",
                "quantity" => $cantidad,
                "unit_price" => 100,
                "currency_id" => "ARS"
            ]
        ],
        "payer" => [
            "name" => $request->nombre,
            "email" => $request->email,
            "identification" => [
                "type" => "DNI",
                "number" => $request->dni
            ]
        ],
        "back_urls" => [
            "success" => route("entradas.success"),
            "failure" => route("entradas.failure"),
            "pending" => route("entradas.pending")
        ],
        "auto_return" => "approved"
    ]);

    return redirect($preference->init_point);
} catch (\Exception $e) {
    // Mostramos todo para debugging
    dd([
        'message' => $e->getMessage(),
        'class' => get_class($e),
        'trace' => $e->getTraceAsString(),
        'response' => method_exists($e, 'getApiResponse') ? $e->getApiResponse() : 'No API response'
    ]);
}

}

  public function success(Request $request)
    {
        // Para testear, mostramos todos los parámetros que trae MercadoPago
        return response()->json([
            'mensaje' => 'Pago exitoso - redirigido correctamente',
            'query_params' => $request->all()
        ]);
    }




    public function failure()
    {
        return view('entradas.failure');
    }

    public function pending()
    {
        return view('entradas.pending');
    }
}

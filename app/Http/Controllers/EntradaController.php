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

public function success(BrevoMailService $brevo)
{
    $datos = session('compra');
    if (!$datos) {
        return redirect('/')->with('error', 'Datos de compra no encontrados.');
    }

    $entradas = [];
    $numeroEntrada = 301;

    for ($i = 0; $i < $datos['cantidad']; $i++) {
        $codigoUnico = Str::uuid();

        $qrData = json_encode([
            'evento' => 'Mi Evento Especial',
            'codigo' => $codigoUnico,
            'nombre' => $datos['nombre'],
            'dni'    => $datos['dni'],
            'fecha'  => now()->toDateString(),
            'valido' => true
        ]);

        $qr = Builder::create()
            ->writer(new PngWriter())
            ->data($qrData)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->build();

        $qrFilename = $codigoUnico . '.png';
        Storage::disk('public')->put('qrcodes/' . $qrFilename, $qr->getString());

        $qrUrl = asset('storage/qrcodes/' . $qrFilename);

        $entrada = [
            'persona' => [
                'nombre' => $datos['nombre'],
                'email'  => $datos['email'],
                'dni'    => $datos['dni']
            ],
            'qr_url' => $qrUrl,
            'codigo' => $codigoUnico,
            'numero' => $numeroEntrada++,
            'qr_data' => $qrData,
        ];

        $entradas[] = $entrada;
    }

    // Enviar mail una vez con todas las entradas
    $htmlContent = view('emails.entrada-generada-multiple', ['entradas' => $entradas])->render();
    $brevo->enviarEntrada($htmlContent, [
        'nombre' => $datos['nombre'],
        'email'  => $datos['email']
    ]);

    session()->forget('compra');

    return view('entradas.qr', [
        'entradas' => $entradas,
        'precio_unitario' => 500,
        'total' => count($entradas) * 500
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

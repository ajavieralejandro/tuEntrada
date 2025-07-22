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

 public function success(Request $request, BrevoMailService $brevo)
{
    try {
        $preferenceId = $request->input('preference_id');
        if (!$preferenceId) {
            return redirect('/')->with('error', 'No se recibió preference_id.');
        }

        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        $client = new PreferenceClient();

        $preference = $client->get($preferenceId);

        $externalRefJson = $preference->external_reference ?? null;
        if (!$externalRefJson) {
            return redirect('/')->with('error', 'No se encontró external_reference.');
        }

        $datos = json_decode($externalRefJson, true);
        if (!$datos || !isset($datos['email'])) {
            return redirect('/')->with('error', 'Datos de compra inválidos.');
        }

        $cantidad = $datos['cantidad'] ?? 1;
        $nombre = $datos['nombre'] ?? 'Cliente';
        $email = $datos['email'];
        $dni = $datos['dni'] ?? null;

        $entradas = [];
        $numeroEntrada = 301;

        for ($i = 0; $i < $cantidad; $i++) {
            $codigoUnico = \Str::uuid();

            $qrData = json_encode([
                'evento' => 'Mi Evento Especial',
                'codigo' => $codigoUnico,
                'nombre' => $nombre,
                'dni'    => $dni,
                'fecha'  => now()->toDateString(),
                'valido' => true
            ]);

            $qr = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode\Writer\PngWriter())
                ->data($qrData)
                ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh())
                ->size(300)
                ->margin(10)
                ->build();

            $qrFilename = $codigoUnico . '.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put('qrcodes/' . $qrFilename, $qr->getString());

            $qrUrl = asset('storage/qrcodes/' . $qrFilename);

            $entrada = [
                'persona' => [
                    'nombre' => $nombre,
                    'email'  => $email,
                    'dni'    => $dni
                ],
                'qr_url' => $qrUrl,
                'codigo' => $codigoUnico,
                'numero' => $numeroEntrada++,
                'qr_data' => $qrData,
            ];

            $entradas[] = $entrada;
        }

        // Intentar enviar mail, pero si falla no corta el flujo
        try {
            $htmlContent = view('emails.entrada-generada-multiple', ['entradas' => $entradas])->render();
            $brevo->enviarEntrada($htmlContent, [
                'nombre' => $nombre,
                'email'  => $email
            ]);
        } catch (\Exception $e) {
            \Log::error('Error enviando mail de entradas: ' . $e->getMessage());
        }

        return view('entradas.qr', [
            'entradas' => $entradas,
            'precio_unitario' => 500,
            'total' => count($entradas) * 500
        ]);
    } catch (\Exception $e) {
        dd([
            'message' => $e->getMessage(),
            'class' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'response' => method_exists($e, 'getApiResponse') ? $e->getApiResponse() : 'No API response'
        ]);
    }
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

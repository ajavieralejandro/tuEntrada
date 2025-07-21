<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use App\Services\BrevoMailService;


class EntradaController extends Controller
{

public function procesar(Request $request, BrevoMailService $brevo)
{
    $request->validate([
        'cantidad' => 'required|integer|min:1',
        'personas' => 'required|array|min:1',
        'personas.*.nombre' => 'required|string',
        'personas.*.email' => 'required|email',
        'personas.*.dni' => 'required|string',
    ]);

    $entradas = [];
    $numeroEntrada = 301;

    foreach ($request->personas as $persona) {
        $codigoUnico = Str::uuid();

        $qrData = json_encode([
            'evento' => 'Mi Evento Especial',
            'codigo' => $codigoUnico,
            'nombre' => $persona['nombre'],
            'dni' => $persona['dni'],
            'fecha' => now()->toDateString(),
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
            'persona' => $persona,
            'qr_url' => $qrUrl,
            'codigo' => $codigoUnico,
            'numero' => $numeroEntrada++,
            'qr_data' => $qrData,
        ];

        $entradas[] = $entrada;

        $htmlContent = view('emails.entrada-generada', ['entrada' => $entrada])->render();

$brevo->enviarEntrada($htmlContent, $entrada['persona']);
    }

    return view('entradas.qr', [
        'entradas' => $entradas,
        'precio_unitario' => 500,
        'total' => count($entradas) * 500
    ]);
}


    public function procesar2(Request $request)
    {
        // Paso 1: Configuración del token
        $accessToken = config('services.mercadopago.access_token');
        if (empty($accessToken)) {
            return response()->json([
                'error' => 'Token de MercadoPago no configurado. Verifica tu archivo .env.'
            ], 500);
        }

        // Paso 2: Construcción de las URLs de retorno
        $baseUrl = rtrim(config('app.url'), '/');
        $urls = [
            'success' => $baseUrl . '/entradas/success',
            'failure' => $baseUrl . '/entradas/failure',
            'pending' => $baseUrl . '/entradas/pending'
        ];

        // Paso 3: Definir preferencia
        $preferenceData = [
            "items" => [
                [
                    "title" => "Entrada Evento",
                    "quantity" => max(1, (int) $request->input('cantidad', 1)),
                    "unit_price" => max(1, (float) $request->input('precio', 100.00)),
                    "currency_id" => "ARS"
                ]
            ],
            "back_urls" => $urls,
            //"auto_return" => "approved"
        ];

        // Paso 4: Crear preferencia en MercadoPago
        try {
            MercadoPagoConfig::setAccessToken($accessToken);
            $client = new PreferenceClient();
            $preference = $client->create($preferenceData);

            return redirect()->away($preference->init_point);
        } catch (MPApiException $e) {
            $apiResponse = method_exists($e, 'getApiResponse') ? $e->getApiResponse() : null;
            $content = null;
            if ($apiResponse) {
                $content = method_exists($apiResponse, 'getContent') ? $apiResponse->getContent() : null;
            }

            return response()->json([
                'error' => $e->getMessage(),
                'api_response_content' => $content,
                'datos_enviados' => $preferenceData
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Métodos de retorno desde MercadoPago
    public function success()
    {
        return view('entradas.success'); // Crea esta vista
    }

    public function failure()
    {
        return view('entradas.failure'); // Crea esta vista
    }

    public function pending()
    {
        return view('entradas.pending'); // Crea esta vista
    }
}

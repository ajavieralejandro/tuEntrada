<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;

class EntradaController extends Controller
{
    public function procesar(Request $request)
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

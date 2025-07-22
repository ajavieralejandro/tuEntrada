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
use Illuminate\Support\Facades\Log;

class EntradaController extends Controller
{
    public function procesar(Request $request)
    {
        $accessToken = config('services.mercadopago.access_token');
        MercadoPagoConfig::setAccessToken($accessToken);

        $client = new PreferenceClient();

        $cantidad = (int) $request->input('cantidad');

        try {
            $externalReference = json_encode([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'dni' => $request->dni,
                'cantidad' => $cantidad
            ]);

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
                "auto_return" => "approved",
                "external_reference" => $externalReference
            ]);

            return redirect($preference->init_point);
        } catch (\Exception $e) {
            Log::error('Error al crear preferencia de MercadoPago: ' . $e->getMessage());
            return back()->with('error', 'Ocurrió un error al procesar tu solicitud.');
        }
    }


public function success(Request $request, BrevoMailService $brevo)
{
    try {
        $externalReference = $request->query('external_reference');

        $datos = session('compra');

        if (!$datos || $datos['id'] !== $externalReference) {
            return redirect('/')->with('error', 'Datos de compra inválidos.');
        }

        $cantidad = $datos['cantidad'] ?? 1;
        $nombre = $datos['nombre'] ?? 'Cliente';
        $email = $datos['email'] ?? null;
        $dni = $datos['dni'] ?? null;

        $entradas = [];
        $numeroEntrada = 301;

        for ($i = 0; $i < $cantidad; $i++) {
            $codigoUnico = Str::uuid();

            $qrData = json_encode([
                'evento' => 'Mi Evento Especial',
                'codigo' => $codigoUnico,
                'nombre' => $nombre,
                'dni'    => $dni,
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

            $entradas[] = [
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
        }

        // Enviar un solo mail con todas las entradas
        $email_enviado = false;
        try {
            $htmlContent = view('emails.entrada-generada-multiple', [
                'entradas' => $entradas,
                'nombre' => $nombre,
                'total' => $cantidad * 100 // ajusta precio si es distinto
            ])->render();

            $brevo->enviarEntrada($htmlContent, [
                'nombre' => $nombre,
                'email' => $email
            ]);

            $email_enviado = true;
            Log::info("Email enviado correctamente a: {$email}");
        } catch (\Exception $e) {
            Log::error("Error al enviar email: " . $e->getMessage());
        }

        // Limpiar sesión para que no se repita
        session()->forget('compra');

        return view('entradas.qr', [
            'entradas' => $entradas,
            'precio_unitario' => 100,
            'total' => $cantidad * 100,
            'email_enviado' => $email_enviado
        ]);

    } catch (\Exception $e) {
        Log::error("Error en success: " . $e->getMessage());
        return redirect('/')->with('error', 'Ocurrió un error al procesar tu compra.');
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

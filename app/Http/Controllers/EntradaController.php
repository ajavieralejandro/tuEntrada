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
use App\Models\Entrada;

class EntradaController extends Controller
{


public function index()
{
    $entradas = Entrada::orderBy('created_at', 'desc')->get();
    return view('entradas.index', compact('entradas'));
}
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
                        "unit_price" => 18000,
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

        // Generar entradas y códigos QR
for ($i = 0; $i < $cantidad; $i++) {
    $codigoUnico = Str::uuid();

    $qrData = json_encode([
        'evento' => 'Agitando Pañuelos',
        'codigo' => $codigoUnico,
        'nombre' => $nombre,
        'dni'    => $dni,
        'fecha'  => '2025-09-27', // 🔹 Fecha fija para el 27/09/2025
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


        // Enviar email con las entradas
        try {
            $htmlContent = view('emails.entrada-generada', [ // CORRECCIÓN AQUÍ: 'emails' en lugar de 'emails'
                'entradas' => $entradas,
                'nombre' => $nombre,
                'total' => $cantidad * 100
            ])->render();

            $brevo->enviarEntrada($htmlContent, [
                'nombre' => $nombre,
                'email' => $email
            ]);

            Log::info('Email enviado correctamente a: ' . $email);
        } catch (\Exception $e) {
            Log::error('Error al enviar email: ' . $e->getMessage());
            // Continuamos mostrando las entradas aunque falle el email
        }

        return view('entradas.qr', [
            'entradas' => $entradas,
            'precio_unitario' => 18000,
            'total' => $cantidad * 100,
            'email_enviado' => !isset($e)
        ]);

    } catch (\Exception $e) {
        Log::error('Error en success: ' . $e->getMessage());
        return redirect('/')->with('error', 'Ocurrió un error al procesar tu compra.');
    }
}
 public function usarDesdeQR(Request $request)
    {
        $validated = $request->validate([
            'codigo_qr' => ['required', 'string'],
        ]);

        $codigo = $validated['codigo_qr'];

        try {
            $entrada = DB::transaction(function () use ($codigo) {
                // Bloqueo pesimista para evitar doble uso simultáneo
                $entrada = Entrada::where('codigo_qr', $codigo)
                    ->lockForUpdate()
                    ->first();

                if (!$entrada) {
                    abort(404, 'Entrada no encontrada.');
                }

                if (!$entrada->valido) {
                    abort(response()->json([
                        'status'  => 'invalid',
                        'message' => 'La entrada no es válida.',
                    ], 422));
                }

                if ($entrada->usada) {
                    abort(response()->json([
                        'status'  => 'already_used',
                        'message' => 'La entrada ya fue utilizada.',
                        'entrada' => [
                            'evento' => $entrada->evento,
                            'nombre' => $entrada->nombre,
                            'dni'    => $entrada->dni,
                            'fecha'  => $entrada->fecha,
                        ],
                    ], 409));
                }

                // Marcar como usada
                $entrada->usada = true;
                $entrada->save();

                return $entrada;
            }, 3); // reintentos en caso de contención

            return response()->json([
                'status'  => 'ok',
                'message' => 'Entrada marcada como usada.',
                'entrada' => [
                    'evento'  => $entrada->evento,
                    'nombre'  => $entrada->nombre,
                    'dni'     => $entrada->dni,
                    'fecha'   => $entrada->fecha,
                    'usada'   => (bool) $entrada->usada,
                    'qr_path' => $entrada->qr_path,
                    'updated' => optional($entrada->updated_at)->toIso8601String(),
                ],
            ], 200);

        } catch (HttpException $e) {
            // Devolver tal cual los 404/409/422 ya armados
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al usar entrada: '.$e->getMessage(), ['codigo_qr' => $codigo]);
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo procesar el uso de la entrada.',
            ], 500);
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

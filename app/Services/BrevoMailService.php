<?php

namespace App\Services;

use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class BrevoMailService
{
    protected TransactionalEmailsApi $api;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            config('services.brevo.api_key')
        );

        $this->api = new TransactionalEmailsApi(new Client(), $config);
    }

 public function enviarEntrada(string $htmlContent, array $destinatario): void
{
    $email = new SendSmtpEmail([
        'subject' => 'Tu entrada para Mi Evento Especial',
        'htmlContent' => $htmlContent,
        'sender' => [
            'name' => config('services.brevo.sender_name'),
            'email' => config('services.brevo.sender_email'),
        ],
        'to' => [[
            'email' => $destinatario['email'],
            'name' => $destinatario['nombre'],
        ]],
    ]);

    try {
        $response = $this->api->sendTransacEmail($email);

        // Mostrar la respuesta directamente
        dd($response);
    } catch (\Exception $e) {
        dd('Error al enviar email con Brevo: ' . $e->getMessage(), $e);
    }
}

}

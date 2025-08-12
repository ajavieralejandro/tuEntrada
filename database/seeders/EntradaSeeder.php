<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entrada;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class EntradaSeeder extends Seeder
{
    public function run()
    {
        // Increase limits for large operations
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        // Clean previous data
        $this->cleanPreviousData();

        $fechaEvento = Carbon::now()->addDays(30);
        $successCount = 0;
        $errors = [];

        // Create directory if it doesn't exist
        Storage::makeDirectory('public/entradas');

        for ($i = 0; $i < 300; $i++) {
            $codigo = Str::padLeft($i, 3, '0');

            try {
                $qrResult = $this->generateAndSaveQr($codigo, $fechaEvento);

                Entrada::create([
                    'evento' => 'Mi Evento Especial',
                    'codigo_qr' => $codigo,
                    'nombre' => "Invitado {$codigo}",
                    'dni' => mt_rand(10000000, 99999999),
                    'fecha' => $fechaEvento,
                    'valido' => true,
                    'usada' => false,
                    'qr_path' => $qrResult['public_path']
                ]);

                $successCount++;

                // Show progress every 50 entries
                if ($i % 50 === 0) {
                    $this->command->info("Procesadas {$i} entradas...");
                }
            } catch (\Exception $e) {
                $errors[] = "✖ Error en código {$codigo}: " . $e->getMessage();
                $this->command->warn(end($errors));
            }
        }

        $this->command->info("🎉 Se generaron {$successCount}/300 entradas correctamente.");

        if (!empty($errors)) {
            $this->command->error("❌ Se encontraron errores:");
            foreach ($errors as $error) {
                $this->command->warn($error);
            }
        }
    }

    protected function cleanPreviousData()
    {
        // Truncate the table
        Entrada::truncate();

        // Clean QR code directory
        $directory = 'public/entradas';
        if (Storage::exists($directory)) {
            $files = Storage::files($directory);
            if (count($files) > 0) {
                Storage::delete($files);
            }
        }
    }

    protected function generateAndSaveQr($codigo, $fechaEvento)
    {
        $qrData = [
            'evento' => 'Mi Evento Especial',
            'codigo' => $codigo,
            'nombre' => "Invitado {$codigo}",
            'fecha' => $fechaEvento->toDateString(),
            'valido' => true
        ];

        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data(json_encode($qrData))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        // Define paths
        $filename = "entrada_{$codigo}.png";
        $storagePath = "entradas/{$filename}"; // Changed to simpler path
        $publicPath = "storage/entradas/{$filename}"; // Public access path

        // Save the file
        Storage::put("public/{$storagePath}", $qrCode->getString());

        return [
            'filename' => $filename,
            'storage_path' => "public/{$storagePath}",
            'public_path' => $publicPath
        ];
    }
}

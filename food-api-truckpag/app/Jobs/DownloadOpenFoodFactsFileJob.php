<?php

namespace App\Jobs;

use App\Jobs\ImportOpenFoodFactsChunkJob;
use App\Models\ImportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DownloadOpenFoodFactsFileJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(public string $filename) {}

    public function handle()
    {
        set_time_limit(0);

        ini_set('memory_limit', '2048M');

        $url = "https://static.openfoodfacts.org/data/delta/{$this->filename}";
        $gzPath = storage_path("app/temp/{$this->filename}");
        $jsonPath = str_replace('.json.gz', '.json', $gzPath);

        // Download
        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 300,
            'connect_timeout' => 30,
        ])->get($url);
        file_put_contents($gzPath, $response->body());

        // Descompactar o .gz para .json
        $gzStream = fopen("compress.zlib://$gzPath", 'rb');
        $jsonStream = fopen($jsonPath, 'wb');
        stream_copy_to_stream($gzStream, $jsonStream);
        fclose($gzStream);
        fclose($jsonStream);

        // Conta total de linhas do JSON (NDJSON)
        $totalLines = 0;
        $handle = fopen($jsonPath, 'r');
        while (!feof($handle)) {
            $line = trim(fgets($handle));
            if (!empty($line)) $totalLines++;
        }
        fclose($handle);

        // Importar no máximo 100 registros — tudo em 1 job
        ImportOpenFoodFactsChunkJob::dispatch($jsonPath, 0, 100, $this->filename)->onQueue('high');


        // Salva histórico (apenas 1 vez)
        ImportHistory::create([
            'filename' => $this->filename,
            'imported_at' => now(),
        ]);

        // Remove arquivos
        if (file_exists($gzPath)) unlink($gzPath);

        Log::info("Arquivo baixado e chunks enfileirados: {$this->filename}");
    }
}

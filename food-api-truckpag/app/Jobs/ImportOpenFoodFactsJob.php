<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ImportHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ImportOpenFoodFactsJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $filename;

    /**
     * Crie uma nova instância de job.
     *
     * @param  string  $filename
     * @return void
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Execute o job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Iniciando importação para {$this->filename}");
        set_time_limit(0);

        ini_set('memory_limit', '2048M');

        $url = "https://static.openfoodfacts.org/data/delta/{$this->filename}";

        try {
            if (!$this->filename) {
                Log::error("Filename não foi fornecido para o job.");
                return;
            }


            // 1. Baixar o arquivo .gz
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 300,
                'connect_timeout' => 30,
            ])->get($url);

            if (!$response->successful()) {
                Log::error("Erro ao baixar o arquivo: {$this->filename}");
            }

            // 2. Preparar paths
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $gzPath = "{$tempDir}/{$this->filename}";
            $jsonPath = str_replace('.json.gz', '.json', $gzPath);

            file_put_contents($gzPath, $response->body());

            // 3. Descompactar .gz para .json
            $gzStream = fopen("compress.zlib://$gzPath", 'rb');
            $jsonStream = fopen($jsonPath, 'wb');

            if (!$gzStream || !$jsonStream) {
                Log::error("rro ao abrir arquivos para descompactação");
            }

            stream_copy_to_stream($gzStream, $jsonStream);
            fclose($gzStream);
            fclose($jsonStream);

            if (!file_exists($jsonPath)) {
                Log::error("Arquivo JSON não encontrado");
                return;
            }

            // 4. Processar NDJSON linha a linha
            $handle = fopen($jsonPath, 'r');
            $maxItems = 100;


            $chunkSize = 100; // quantidade de produtos por insert
            $buffer = [];
            $imported = 0;
            $counter = 0;

            while (!feof($handle)) {
                $line = trim(fgets($handle));

                if (empty($line)) continue;

                $item = json_decode($line, true);

                if (!is_array($item) || !isset($item['code'])) continue;

                $code = isset($item['code']) ? (string) preg_replace('/\D/', '', (string) $item['code']) : null;

                // Evita duplicados
                if (Product::where('code', $code)->exists()) {
                    continue;
                }

                $buffer[] = [
                    'code' => $code,
                    'url' => $item['url'] ?? null,
                    'creator' => $item['creator'] ?? null,
                    'created_t' => $item['created_t'] ?? null,
                    'created_datetime' => $item['created_datetime'] ?? null,
                    'last_modified_t' => $item['last_modified_t'] ?? null,
                    'last_modified_datetime' => $item['last_modified_datetime'] ?? null,
                    'product_name' => $item['product_name'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'brands' => $item['brands'] ?? null,
                    'categories' => $item['categories'] ?? null,
                    'labels' => $item['labels'] ?? null,
                    'ingredients_text' => $item['ingredients_text'] ?? null,
                    'traces' => $item['traces'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'status' => 'published',
                ];


                // 5. Inserir no banco
                if (count($buffer) >= $chunkSize) {
                    try {
                        // Sanitiza os campos que precisam ser string
                        $now = now()->toDateTimeString();
                        foreach ($buffer as &$productData) {
                            $productData['imported_t'] = $now;
                            
                        }
                        unset($productData); // sempre bom limpar referência

                        Product::insert($buffer);
                        $imported += count($buffer);
                    } catch (\Throwable $e) {
                        Log::warning("Erro ao inserir chunk de produtos: " . $e->getMessage());
                    }

                    $buffer = []; // esvazia o buffer
                }


                $counter++;
                if ($maxItems && $counter >= $maxItems) break;
            }

            // Inserir qualquer resto que tenha no buffer
            if (!empty($buffer)) {
                try {
                    Product::insert($buffer);
                    $imported += count($buffer);
                } catch (\Throwable $e) {
                    Log::warning("Erro ao inserir último chunk de produtos: " . $e->getMessage());
                }
            }

            fclose($handle);


            // 6. Histórico de importação
            ImportHistory::create([
                'filename' => $this->filename,
                'imported_at' => now(),
            ]);

            // 7. Limpeza
            if (file_exists($gzPath)) unlink($gzPath);
            if (file_exists($jsonPath)) unlink($jsonPath);

            // 8. Sucesso!
            Log::info('Importação concluída com sucesso!');
            
        } catch (\Exception $e) {
            // Caso ocorra algum erro em qualquer parte do processo, logamos o erro
            Log::error("Erro inesperado durante a importação do arquivo {$this->filename}: {$e->getMessage()}");
        }
    }
}

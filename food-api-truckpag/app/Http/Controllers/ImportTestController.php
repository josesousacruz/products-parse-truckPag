<?php

namespace App\Http\Controllers;

use App\Jobs\ImportOpenFoodFactsJob;
use App\Models\Product;
use App\Models\ImportHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use JsonMachine\Items;

class ImportTestController extends Controller
{

    public function importJob($filename)
    {
        // Dispara o job
        ImportOpenFoodFactsJob::dispatch($filename);

        // Retorna uma resposta informando que o job foi disparado
        return response()->json([
            'message' => 'Importação do arquivo em andamento!',
            'filename' => $filename
        ]);
    }
    
    public function import($filename)
    {
        ini_set('max_execution_time', 300); // 5 minutos

        ini_set('memory_limit', '2048M');

        $url = "https://static.openfoodfacts.org/data/delta/{$filename}";

        // 1. Baixar o arquivo .gz
        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 300,
            'connect_timeout' => 30,
        ])->get($url);

        if (!$response->successful()) {
            Log::error("Erro ao baixar o arquivo: {$filename}");
            return response()->json(['error' => 'Falha ao baixar o arquivo'], 500);
        }

        // 2. Preparar paths
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $gzPath = "{$tempDir}/{$filename}";
        $jsonPath = str_replace('.json.gz', '.json', $gzPath);

        file_put_contents($gzPath, $response->body());

        // 3. Descompactar .gz para .json
        $gzStream = fopen("compress.zlib://$gzPath", 'rb');
        $jsonStream = fopen($jsonPath, 'wb');

        if (!$gzStream || !$jsonStream) {
            return response()->json(['error' => 'Erro ao abrir arquivos para descompactação'], 500);
        }

        stream_copy_to_stream($gzStream, $jsonStream);
        fclose($gzStream);
        fclose($jsonStream);

        // 4. Processar NDJSON linha a linha
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'Arquivo JSON não encontrado'], 500);
        }

        $handle = fopen($jsonPath, 'r');
        $products = [];
        $maxItems = 100; // ajuste para null se quiser importar tudo
        $counter = 0;

        while (!feof($handle)) {
            $line = trim(fgets($handle));

            if (empty($line)) continue;

            $item = json_decode($line, true);

            if (!is_array($item) || !isset($item['code'])) continue;


            $products[] = [
                'code' => (int) trim($item['code'], "\""),
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
                'imported_t' => now(),
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $counter++;
            if ($maxItems && $counter >= $maxItems) break;
        }

        fclose($handle);

        // 5. Inserir no banco
        if (!empty($products)) {
            Product::insert($products);
        }

        // 6. Histórico de importação
        ImportHistory::create([
            'filename' => $filename,
            'imported_at' => now(),
        ]);

        // 7. Limpeza
        if (file_exists($gzPath)) unlink($gzPath);
        if (file_exists($jsonPath)) unlink($jsonPath);

        // 8. Sucesso!
        return response()->json([
            'message' => 'Importação concluída com sucesso!',
            'registros_importados' => count($products),
        ]);
    }
}

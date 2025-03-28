<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ImportHistory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenFoodFactsImportService
{
    public function importFromGzStream(string $filename, int $maxItems = 100)
    {
        Log::info("Importando arquivo streaming: {$filename}");
        $url = "https://static.openfoodfacts.org/data/delta/{$filename}";

        $client = new Client([
            'verify' => false,
            'timeout' => 0,
        ]);

        $response = $client->request('GET', $url, ['stream' => true]);
        $body = $response->getBody();

        $inflateContext = inflate_init(ZLIB_ENCODING_GZIP);
        $buffer = '';
        $productsBuffer = [];

        $chunkSize = 100;
        $imported = 0;
        $counter = 0;

        while (!$body->eof()) {
            $compressedChunk = $body->read(8192);
            if ($compressedChunk === '') continue;

            $decompressedChunk = inflate_add($inflateContext, $compressedChunk);
            $buffer .= $decompressedChunk;

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlinePos);
                $buffer = substr($buffer, $newlinePos + 1);

                $line = trim($line);
                if (empty($line)) continue;

                $item = json_decode($line, true);
                if (!is_array($item) || !isset($item['code'])) continue;

                $code = preg_replace('/\D/', '', (string) $item['code']);
                if (Product::where('code', $code)->exists()) continue;

                $productsBuffer[] = [
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
                    'imported_t' => now()->toDateTimeString(),
                ];

                if (count($productsBuffer) >= $chunkSize) {
                    try {
                        Product::insert($productsBuffer);
                        $imported += count($productsBuffer);
                    } catch (\Throwable $e) {
                        Log::warning("Erro ao inserir chunk: " . $e->getMessage());
                    }
                    $productsBuffer = [];
                }

                $counter++;
                if ($maxItems && $counter >= $maxItems) break 2;
            }
        }

        if (!empty($productsBuffer)) {
            try {
                Product::insert($productsBuffer);
                $imported += count($productsBuffer);
            } catch (\Throwable $e) {
                Log::warning("Erro ao inserir último chunk: " . $e->getMessage());
            }
        }

        ImportHistory::create([
            'filename' => $filename,
            'imported_at' => now(),
        ]);

        Log::info("Importação concluída com sucesso! Total importado: {$imported}");

        return $imported;
    }
}

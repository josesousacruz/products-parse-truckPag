<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportOpenFoodFactsChunkJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public string $jsonPath,
        public int $offset,
        public int $limit,
        public string $filename
    ) {}

    public function handle()
    {
        set_time_limit(0);

        ini_set('memory_limit', '2048M');
        
        $handle = fopen($this->jsonPath, 'r');
        $buffer = [];
        $count = 0;

        for ($i = 0; $i < $this->offset && !feof($handle); $i++) {
            fgets($handle);
        }

        while (!feof($handle) && $count < $this->limit) {
            $line = trim(fgets($handle));
            if (empty($line)) continue;

            $item = json_decode($line, true);
            if (!is_array($item) || !isset($item['code'])) continue;

            $code = preg_replace('/\\D/', '', (string) $item['code']);

            if (Product::where('code', $code)->exists()) continue;

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

            $count++;
        }

        fclose($handle);
        if (!empty($buffer)) {
            Product::insert($buffer);
            Log::info("Chunk importado: offset {$this->offset}");
        }

        if ($this->offset === 0 && file_exists($this->jsonPath)) {
            unlink($this->jsonPath);
        }        
    }
}

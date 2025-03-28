<?php

namespace App\Console\Commands;

use App\Jobs\DownloadOpenFoodFactsFileJob;
use App\Jobs\ImportOpenFoodFactsJob;
use App\Models\ImportHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportOpenFoodFacts extends Command
{
    /**
     * O nome e assinatura do console command.
     *
     * @var string
     */
    protected $signature = 'import:openfoodfacts';

    /**
     * A descrição do console command.
     *
     * @var string
     */
    protected $description = 'Importa os dados mais recentes do Open Food Facts para a base de dados';

    /**
     * Execute o console command.
     *
     * @return void
     */
    public function handle()
    {
        $url = 'https://static.openfoodfacts.org/data/delta/index.txt';

        // Obter a lista de arquivos JSON disponíveis
        $response = Http::withOptions([
            'verify' => false,
        ])->get($url);
        
        if ($response->successful()) {
            $files = explode("\n", $response->body());

            foreach ($files as $file) {
                $file = trim($file);
                if (!empty($file)) {

                    $existingImport = ImportHistory::where('filename', $file)->first();

                    if($existingImport){
                        $this->info("Arquivo já importado: {$file}");
                    }else{
                        ImportOpenFoodFactsJob::dispatch($file)->onQueue('high');
                        // DownloadOpenFoodFactsFileJob::dispatch($file)->onQueue('low');
                        // Enfileirar o job para cada arquivo
                        $this->info("Job enfileirado para o arquivo: {$file}");
                    }
                }
            }

            $this->info('Todos os jobs foram enfileirados!');
        } else {
            $this->error('Falha ao obter a lista de arquivos.');
        }
    }
}

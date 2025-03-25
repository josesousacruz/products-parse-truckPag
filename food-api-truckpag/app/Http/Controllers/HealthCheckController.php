<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ImportHistory;
use Carbon\Carbon;

class HealthCheckController extends Controller
{
    /**
     * @OA\Get(
     *     path="/",
     *     tags={"Health"},
     *     summary="Verifica o status da API",
     *     description="Retorna informações sobre o estado atual da API, banco de dados, cron, uptime e uso de memória.",
     *     @OA\Response(
     *         response=200,
     *         description="Status da API retornado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="api_details", type="string", example="API Status - Detalhes da API"),
     *             @OA\Property(property="db_status", type="string", example="Conexão com o banco de dados OK"),
     *             @OA\Property(property="last_cron_execution", type="string", example="2024-03-22 02:00:00"),
     *             @OA\Property(property="uptime", type="string", example="Online há 5 dias, 3 horas"),
     *             @OA\Property(property="memory_usage", type="string", example="15.23 MB")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao verificar o status da API"
     *     )
     * )
     */
    public function index()
    {
        // Verificar a conexão com o banco de dados
        try {
            DB::connection()->getPdo();
            $dbStatus = "Conexão com o banco de dados OK";
        } catch (\Exception $e) {
            $dbStatus = "Erro na conexão com o banco de dados: " . $e->getMessage();
        }

        // Recuperar a última vez que o CRON foi executado (da tabela ImportHistory)
        $lastCron = ImportHistory::latest('imported_at')->first();
        $lastCronTime = $lastCron ? $lastCron->imported_at->toDateTimeString() : 'Nunca executado';

        // Calcular o tempo online (para Windows)
        $uptime = $this->getSystemUptimeWindows();

        //$uptime = exec('uptime -p');  // Para sistemas Linux


        // Uso de memória
        $memoryUsage = memory_get_usage(true); // Memória em bytes
        $memoryUsageFormatted = $this->formatBytes($memoryUsage);

        return response()->json([
            'api_details' => 'API Status - Detalhes da API',
            'db_status' => $dbStatus,
            'last_cron_execution' => $lastCronTime,
            'uptime' => $uptime,
            'memory_usage' => $memoryUsageFormatted
        ]);
    }

    // Método para obter o tempo de atividade do sistema no Windows
    private function getSystemUptimeWindows()
    {
        // Usando PowerShell para obter o tempo de atividade
        $uptimeRaw = shell_exec('powershell -Command "([System.Diagnostics.Stopwatch]::GetTimestamp())"');
        $uptimeRaw = (int)$uptimeRaw;

        // Podemos utilizar a data e hora do sistema de quando o computador foi iniciado
        $startTime = shell_exec('powershell -Command "(Get-CimInstance -ClassName Win32_OperatingSystem).LastBootUpTime"');
        $startTime = strtotime($startTime);
        $uptime = time() - $startTime; // Tempo de atividade em segundos

        // Converter para formato legível (dias, horas, minutos)
        $days = floor($uptime / (24 * 60 * 60));
        $hours = floor(($uptime - $days * 24 * 60 * 60) / 3600);
        $minutes = floor(($uptime - $days * 24 * 60 * 60 - $hours * 3600) / 60);

        return "up {$hours} hours, {$minutes} minutes";
    }

    // Método para formatar a quantidade de memória de bytes para uma leitura mais amigável
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

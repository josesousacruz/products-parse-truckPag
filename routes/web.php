<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

// use MongoDB\Client;

// Route::get('/products', function () {
//     try {
//         // Criar uma instância do cliente MongoDB
//         $produtos = Product::all();


//         return response()->json([
//             'status' => 'Conectado ao MongoDB com sucesso!',
//             'databases' => $produtos
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'Erro ao conectar ao MongoDB',
//             'message' => $e->getMessage()
//         ]);
//     }
// });


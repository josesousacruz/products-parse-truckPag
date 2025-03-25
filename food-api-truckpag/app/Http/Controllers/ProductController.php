<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;



/**
 * @OA\Info(
 *     title="Food API - Open Food Facts",
 *     version="1.0.0",
 *     description="API para importar, armazenar e gerenciar dados alimentícios."
 * )
 *
 * @OA\Tag(
 *     name="Products",
 *     description="Gerenciamento de produtos"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     tags={"Products"},
     *     summary="Listar produtos",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de produtos paginada"
     *     )
     * )
     */
    public function index()
    {
        try {
            $products = Product::paginate(10);

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter os produtos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * @OA\Post(
     *     path="/api/products",
     *     tags={"Products"},
     *     summary="Criar novo produto",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Produto criado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'code' => 'required|unique:products,code',
                'url' => 'url', 
                'creator' => 'string|max:255',
                'created_t' => 'integer',
                'last_modified_t' => 'integer',
                'product_name' => 'string|max:255',
                'quantity' => 'nullable|string',
                'brands' => 'nullable|string',
                'categories' => 'nullable|string', 
                'labels' => 'nullable|string',
                'cities' => 'nullable|string',
                'purchase_places' => 'nullable|string',
                'stores' => 'nullable|string',
                'ingredients_text' => 'nullable|string',
                'traces' => 'nullable|string',
                'serving_size' => 'nullable|string',
                'serving_quantity' => 'nullable|string',                                                                            
                'nutriscore_score' => 'nullable|string',
                'nutriscore_grade' => 'nullable|string',
                'main_category' => 'nullable|string',
                'image_url' => 'url',
            ]);
    
            // Transformar o 'code' em inteiro (remover as aspas duplas)
            $validatedData['code'] = trim($validatedData['code'], '"');
    
            // Adiciona os campos `imported_t` e `status`
            $validatedData['status'] = 'draft';  // Define o status como 'draft'
            $validatedData['imported_t'] = now(); // Define o campo `imported_t` com o horário atual (data e hora)
    
            if (isset($validatedData['categories'])) {
                $validatedData['categories'] = implode(',', array_map('trim', explode(',', $validatedData['categories'])));
            }
    
            if (isset($validatedData['labels'])) {
                $validatedData['labels'] = implode(',', array_map('trim', explode(',', $validatedData['labels'])));
            }
    
            if (isset($validatedData['traces'])) {
                $validatedData['traces'] = implode(',', array_map('trim', explode(',', $validatedData['traces'])));
            }
    
            // Criar o produto no banco de dados
            $product = Product::create($validatedData);
    
            return response()->json([
                'message' => 'Produto salvo com sucesso!',
                'product' => $product
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar a requisição',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/products/{code}",
     *     tags={"Products"},
     *     summary="Buscar produto por código",
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produto encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Produto não encontrado"
     *     )
     * )
     */
    public function show($code)
    {
        try {
            $product = Product::where('code', $code)->first();

            if (!$product) {
                return response()->json(['message' => 'Produto não encontrado'], 404);
            }

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter o produto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/products/{code}",
     *     tags={"Products"},
     *     summary="Atualizar um produto",
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produto atualizado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Produto não encontrado"
     *     )
     * )
     */
    public function update(Request $request, $code)
    {
        try {
            $product = Product::where('code', $code)->first();

            if (!$product) {
                return response()->json(['message' => 'Produto não encontrado'], 404);
            }

            $product->update($request->all());

            return response()->json([
                'message' => 'Produto atualizado com sucesso!',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar o produto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{code}",
     *     tags={"Products"},
     *     summary="Mover produto para lixeira",
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produto movido para lixeira com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Produto não encontrado"
     *     )
     * )
     */
    public function destroy($code)
    {
        try {
            $product = Product::where('code', $code)->first();

            if (!$product) {
                return response()->json(['message' => 'Produto não encontrado'], 404);
            }

            $product->update(['status' => 'trash']);

            return response()->json(['message' => 'Produto movido para lixeira!']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao mover o produto para a lixeira',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

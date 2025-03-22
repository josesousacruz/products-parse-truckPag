<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = Product::paginate(10); // Retorna 10 produtos por página

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter os produtos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
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
            $validatedData['code'] = (int) trim($validatedData['code'], '"');
    
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
     * Display the specified resource.
     */
    public function show($code)
    {
        try {
            $product = Product::where('code', (int) $code)->first();

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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $code)
    {
        try {
            $product = Product::where('code', (int) $code)->first();

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
     * Remove the specified resource from storage.
     */
    public function destroy($code)
    {
        try {
            $product = Product::where('code', (int) $code)->first();

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

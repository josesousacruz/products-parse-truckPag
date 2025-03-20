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
        $products = Product::paginate(10); // Retorna 10 produtos por página

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // 🔹 Validação de TODOS os campos obrigatórios
            $validatedData = $request->validate([
                'code' => 'required|integer|unique:products,code',
                'status' => 'required|in:draft,trash,published',
                'imported_t' => 'required|date', 
                'url' => 'url', 
                'creator' => 'string|max:255',
                'created_t' => 'integer',
                'last_modified_t' => 'integer',
                'product_name' => 'string|max:255',
                'quantity' => 'string',
                'brands' => 'string',
                'categories' => 'string', 
                'labels' => 'nullable|string',
                'cities' => 'nullable|string',
                'purchase_places' => 'string',
                'stores' => 'nullable|string',
                'ingredients_text' => 'string',
                'traces' => 'nullable|string',
                'serving_size' => 'string',
                'serving_quantity' => 'numeric',
                'nutriscore_score' => 'integer',
                'nutriscore_grade' => 'string|max:1',
                'main_category' => 'string',
                'image_url' => 'url', 
            ]);

            // 🔹 Criar o produto no banco de dados
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
        $product = Product::where('code', $code)->first();

        if (!$product) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $code)
    {
        $product = Product::where('code', $code)->first();

        if (!$product) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        $product->update($request->all());

        return response()->json([
            'message' => 'Produto atualizado com sucesso!',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($code)
    {
        $product = Product::where('code', $code)->first();

        if (!$product) {
            return response()->json(['message' => 'Produto não encontrado'], 404);
        }

        $product->update(['status' => 'trash']);

        return response()->json(['message' => 'Produto movido para lixeira!']);
    }
}

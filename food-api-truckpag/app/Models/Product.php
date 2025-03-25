<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as EloquentModel;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     required={"code"},
 *     @OA\Property(property="code", type="string", example="123456789"),
 *     @OA\Property(property="url", type="string", format="url", example="https://example.com/product"),
 *     @OA\Property(property="creator", type="string", example="admin"),
 *     @OA\Property(property="created_t", type="integer", example=1617842047),
 *     @OA\Property(property="last_modified_t", type="integer", example=1617843055),
 *     @OA\Property(property="product_name", type="string", example="Chocolate Amargo 70%"),
 *     @OA\Property(property="quantity", type="string", example="100g"),
 *     @OA\Property(property="brands", type="string", example="Nestlé"),
 *     @OA\Property(property="categories", type="string", example="Snacks, Doces"),
 *     @OA\Property(property="labels", type="string", example="Sem glúten"),
 *     @OA\Property(property="cities", type="string", example="São Paulo"),
 *     @OA\Property(property="purchase_places", type="string", example="Mercado Central"),
 *     @OA\Property(property="stores", type="string", example="Supermercado X"),
 *     @OA\Property(property="ingredients_text", type="string", example="cacau, açúcar, manteiga de cacau"),
 *     @OA\Property(property="traces", type="string", example="soja"),
 *     @OA\Property(property="serving_size", type="string", example="25g"),
 *     @OA\Property(property="serving_quantity", type="string", example="25"),
 *     @OA\Property(property="nutriscore_score", type="string", example="10"),
 *     @OA\Property(property="nutriscore_grade", type="string", example="C"),
 *     @OA\Property(property="main_category", type="string", example="chocolates"),
 *     @OA\Property(property="image_url", type="string", format="url", example="https://example.com/image.jpg")
 * )
 */
class Product extends EloquentModel
{
    protected $connection = 'mongodb';
    protected $collection = 'products';

    protected $fillable = [
        'code', 'status', 'imported_t', 'url', 'creator', 'created_t', 'last_modified_t',
        'product_name', 'quantity', 'brands', 'categories', 'labels', 'cities',
        'purchase_places', 'stores', 'ingredients_text', 'traces', 'serving_size',
        'serving_quantity', 'nutriscore_score', 'nutriscore_grade', 'main_category', 'image_url'
    ];

    protected $casts = [
        'imported_t' => 'datetime',
        'created_t' => 'integer',
        'last_modified_t' => 'datetime',
        'serving_quantity' => 'float',
        'nutriscore_score' => 'integer',
    ];

    public function setStatusAttribute($value)
    {
        $validStatuses = ['draft', 'trash', 'published'];
        if (!in_array($value, $validStatuses)) {
            throw new \InvalidArgumentException("Status inválido! Use: draft, trash ou published.");
        }
        $this->attributes['status'] = $value;
    }
}

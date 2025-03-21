<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as EloquentModel;

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

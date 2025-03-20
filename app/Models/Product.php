<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model as EloquentModel;

class Product extends EloquentModel
{
    protected $connection = 'mongodb';
    protected $collection = 'products';

    protected $guarded = ['_id'];

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

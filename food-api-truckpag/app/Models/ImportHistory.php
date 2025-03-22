<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class ImportHistory extends Model
{
    use HasFactory;
    protected $connection = 'mongodb';
    protected $collection = 'import_histories';

    protected $fillable = [
        'filename',  
        'imported_at',  
    ];

    protected $casts = [
        'imported_at' => 'datetime',  
    ];
}

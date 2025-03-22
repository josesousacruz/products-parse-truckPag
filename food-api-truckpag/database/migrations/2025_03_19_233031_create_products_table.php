<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('products', function ($collection) {
            $collection->index('code', ['unique' => true]); 
            $collection->index('status'); 
            $collection->index('imported_t'); 
            $collection->index('brands'); 
            $collection->index('nutriscore_score');
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};

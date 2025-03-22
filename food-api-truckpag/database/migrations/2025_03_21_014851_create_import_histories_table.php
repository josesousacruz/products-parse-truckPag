<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->timestamp('imported_at');
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('import_histories');
    }
    
};

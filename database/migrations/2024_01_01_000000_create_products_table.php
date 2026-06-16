<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('merchant_id');
            $table->string('name');
            $table->string('link');
            $table->string('image_link');
            $table->decimal('price', 12, 2);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->string('currency', 3);
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('currency');
            $table->index('price');
            $table->index('name');
            $table->index('link');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

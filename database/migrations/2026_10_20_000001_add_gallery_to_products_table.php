<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional image carousel for a product.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gallery');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 64)->nullable()->after('slug');
            $table->unsignedInteger('sale_price_cents')->nullable()->after('price_cents');
            $table->string('sizes')->nullable()->after('short_description');
        });

        // Existing products already have a warehouse SKU on their first
        // variant. Promote it to the product so the two stay in sync and the
        // product article is unique from the start.
        DB::table('products')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $sku = DB::table('product_variants')
                    ->where('product_id', $product->id)
                    ->orderBy('id')
                    ->value('sku');

                if (is_string($sku) && $sku !== '') {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['sku' => $sku]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropColumn(['sku', 'sale_price_cents', 'sizes']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technique_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency', 3)->default('RUB');
            $table->string('image')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->unsignedInteger('price_cents')->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('type', 32);
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['variant_id', 'created_at']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->string('payment_status', 32)->default('unpaid')->index();
            $table->string('delivery_method', 32)->default('pickup');
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('delivery_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->char('currency', 3)->default('RUB');
            $table->string('contact_name');
            $table->string('contact_phone', 32);
            $table->string('contact_email');
            $table->string('address')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name');
            $table->string('sku');
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total_cents');
            $table->char('currency', 3)->default('RUB');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};

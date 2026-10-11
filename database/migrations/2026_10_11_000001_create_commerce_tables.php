<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->index();
            $table->string('phone', 30)->nullable();
        });
        foreach (['categories', 'collections', 'brands', 'tags'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('name_bn')->nullable();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path')->unique();
            $table->string('alt');
            $table->string('mime');
            $table->unsignedInteger('bytes');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('description_bn')->nullable();
            $table->text('care')->nullable();
            $table->text('size_guide')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('featured')->default(false)->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();
        });
        foreach (['collection', 'tag'] as $relation) {
            Schema::create($relation.'_product', function (Blueprint $table) use ($relation) {
                $table->foreignId($relation.'_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->primary([$relation.'_id', 'product_id']);
            });
        }
        Schema::create('media_product', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['media_id', 'product_id']);
        });
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('size', 30);
            $table->string('color', 50);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unique(['product_id', 'size', 'color']);
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->string('reason');
            $table->timestamps();
        });
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('address');
            $table->string('district');
            $table->string('postcode', 20)->nullable();
            $table->timestamps();
        });
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unique(['cart_id', 'variant_id']);
            $table->timestamps();
        });
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'product_id']);
            $table->timestamps();
        });
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('districts');
            $table->unsignedBigInteger('charge');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('fixed');
            $table->unsignedBigInteger('value');
            $table->unsignedBigInteger('minimum')->default(0);
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('usage_limit')->default(100);
            $table->unsignedInteger('per_customer_limit')->default(1);
            $table->unsignedInteger('uses')->default(0);
            $table->timestamp('expires_at');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('checkout_key')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name');
            $table->string('email')->index();
            $table->string('phone', 30);
            $table->json('address');
            $table->string('shipping_method');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('shipping');
            $table->unsignedBigInteger('total');
            $table->string('currency', 3)->default('BDT');
            $table->string('status')->default('pending')->index();
            $table->string('fulfillment_status')->default('unfulfilled')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('payment_method')->default('cod');
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('reservation_expires_at')->nullable()->index();
            $table->timestamp('stock_consumed_at')->nullable();
            $table->timestamps();
            $table->index(['coupon_id', 'email']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku');
            $table->string('size');
            $table->string('color');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
        });
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->string('status')->default('requested');
            $table->text('admin_note')->nullable();
            $table->timestamp('restocked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('body');
            $table->text('body_bn')->nullable();
            $table->boolean('published')->default(false);
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();
        });
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->text('body')->nullable();
            $table->text('body_bn')->nullable();
            $table->string('url')->nullable();
            $table->string('link_label')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('fields');
            $table->timestamp('created_at');
            $table->index(['subject_type', 'subject_id']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE variants ADD CONSTRAINT variant_stock_valid CHECK (stock >= 0 AND reserved >= 0 AND reserved <= stock)');
            DB::statement('ALTER TABLE variants ADD CONSTRAINT variant_prices_valid CHECK (price > 0 AND (sale_price IS NULL OR (sale_price > 0 AND sale_price <= price)))');
            DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_quantity_valid CHECK (quantity BETWEEN 1 AND 99)');
            DB::statement('ALTER TABLE orders ADD CONSTRAINT order_total_valid CHECK (subtotal >= discount AND total = subtotal - discount + shipping)');
            DB::statement("ALTER TABLE users ADD CONSTRAINT user_role_valid CHECK (role IN ('customer', 'super_admin', 'catalog_manager', 'order_manager', 'content_editor'))");
        }
    }

    public function down(): void
    {
        foreach (['audit_logs', 'content_blocks', 'pages', 'settings', 'return_requests', 'order_items', 'orders', 'coupons', 'shipping_zones', 'wishlists', 'cart_items', 'carts', 'addresses', 'stock_movements', 'variants', 'media_product', 'tag_product', 'collection_product', 'products', 'media', 'tags', 'brands', 'collections', 'categories'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'phone']));
    }
};

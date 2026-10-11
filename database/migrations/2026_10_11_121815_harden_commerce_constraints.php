<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        DB::statement("ALTER TABLE products ADD CONSTRAINT product_status_valid CHECK (status IN ('draft','published','archived'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT order_status_valid CHECK (status IN ('pending','confirmed','shipped','delivered','cancelled'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT payment_status_valid CHECK (payment_status IN ('unpaid','paid','refunded'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT fulfillment_status_valid CHECK (fulfillment_status IN ('unfulfilled','shipped','delivered','cancelled','returned'))");
        DB::statement("ALTER TABLE coupons ADD CONSTRAINT coupon_value_valid CHECK (value > 0 AND uses >= 0 AND uses <= usage_limit AND per_customer_limit > 0 AND type IN ('fixed','percent') AND (type <> 'percent' OR value <= 100))");
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_item_amount_valid CHECK (quantity BETWEEN 1 AND 99 AND unit_price > 0 AND line_total = unit_price * quantity)');
        DB::statement("CREATE OR REPLACE FUNCTION prevent_order_item_mutation() RETURNS trigger LANGUAGE plpgsql AS 'BEGIN RAISE EXCEPTION ''Order item snapshots are immutable''; END;'");
        DB::statement('CREATE TRIGGER order_items_immutable BEFORE UPDATE OR DELETE ON order_items FOR EACH ROW EXECUTE FUNCTION prevent_order_item_mutation()');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        DB::statement('DROP TRIGGER IF EXISTS order_items_immutable ON order_items');
        DB::statement('DROP FUNCTION IF EXISTS prevent_order_item_mutation()');
        foreach (['orders' => ['order_status_valid', 'payment_status_valid', 'fulfillment_status_valid'], 'products' => ['product_status_valid'], 'coupons' => ['coupon_value_valid'], 'order_items' => ['order_item_amount_valid']] as $table => $constraints) {
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE $table DROP CONSTRAINT $constraint");
            }
        }
    }
};

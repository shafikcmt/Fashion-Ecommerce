<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('fabric')->nullable();
            $table->string('fabric_bn')->nullable();
            $table->string('origin')->nullable();
            $table->string('origin_bn')->nullable();
            $table->string('badge', 60)->nullable();
            $table->string('badge_bn', 60)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['fabric', 'fabric_bn', 'origin', 'origin_bn', 'badge', 'badge_bn']));
    }
};

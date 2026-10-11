<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_blocks', fn (Blueprint $table) => $table->string('link_label_bn')->nullable());
        Schema::table('products', function (Blueprint $table) {
            $table->text('care_bn')->nullable();
            $table->text('size_guide_bn')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('content_blocks', fn (Blueprint $table) => $table->dropColumn('link_label_bn'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['care_bn', 'size_guide_bn']));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->decimal('quantity_per_product', 14, 2)->change();
        });

        Schema::table('material_stocks', function (Blueprint $table) {
            $table->decimal('on_hand', 14, 2)->default(0)->change();
            $table->decimal('reorder_level', 14, 2)->nullable()->change();
        });

        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 14, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->decimal('quantity_per_product', 14, 4)->change();
        });

        Schema::table('material_stocks', function (Blueprint $table) {
            $table->decimal('on_hand', 14, 4)->default(0)->change();
            $table->decimal('reorder_level', 14, 4)->nullable()->change();
        });

        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 14, 4)->change();
        });
    }
};

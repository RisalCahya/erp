<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'output_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('output_unit', 50)->nullable()->after('name');
            });
        }

        if (! Schema::hasTable('production_realizations')) {
            Schema::create('production_realizations', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_number')->unique();
                $table->date('transaction_date');
                $table->foreignId('product_id');
                $table->foreign('product_id', 'pr_product_fk')
                    ->references('id')
                    ->on('products')
                    ->restrictOnDelete();
                $table->decimal('finished_good_quantity', 14, 2);
                $table->string('output_unit', 50);
                $table->text('notes')->nullable();
                $table->string('status')->default('draft')->index();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'transaction_date']);
            });
        }

        if (! Schema::hasTable('production_realization_materials')) {
            Schema::create('production_realization_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_realization_id');
                $table->foreign('production_realization_id', 'prm_realization_fk')
                    ->references('id')
                    ->on('production_realizations')
                    ->restrictOnDelete();
                $table->foreignId('material_id');
                $table->foreign('material_id', 'prm_material_fk')
                    ->references('id')
                    ->on('materials')
                    ->restrictOnDelete();
                $table->decimal('quantity_per_output', 14, 2);
                $table->decimal('required_quantity', 14, 2);
                $table->timestamps();

                $table->unique(['production_realization_id', 'material_id']);
            });
        }

        if (! Schema::hasTable('finished_good_stocks')) {
            Schema::create('finished_good_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->unique();
                $table->foreign('product_id', 'fgs_product_fk')
                    ->references('id')
                    ->on('products')
                    ->restrictOnDelete();
                $table->string('unit', 50);
                $table->decimal('on_hand', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('finished_good_stock_movements')) {
            Schema::create('finished_good_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id');
                $table->foreign('product_id', 'fgm_product_fk')
                    ->references('id')
                    ->on('products')
                    ->restrictOnDelete();
                $table->foreignId('production_realization_id')->unique();
                $table->foreign('production_realization_id', 'fgm_realization_fk')
                    ->references('id')
                    ->on('production_realizations')
                    ->restrictOnDelete();
                $table->string('movement_type')->default('production_output');
                $table->decimal('quantity', 14, 2);
                $table->string('unit', 50);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_good_stock_movements');
        Schema::dropIfExists('finished_good_stocks');
        Schema::dropIfExists('production_realization_materials');
        Schema::dropIfExists('production_realizations');

        if (Schema::hasColumn('products', 'output_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('output_unit');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table) {
            $table->decimal('planned_quantity', 14, 2)->change();
        });

        Schema::table('production_plan_materials', function (Blueprint $table) {
            $table->decimal('required_quantity', 14, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_plan_materials', function (Blueprint $table) {
            $table->decimal('required_quantity', 14, 4)->change();
        });

        Schema::table('production_plans', function (Blueprint $table) {
            $table->decimal('planned_quantity', 14, 4)->change();
        });
    }
};

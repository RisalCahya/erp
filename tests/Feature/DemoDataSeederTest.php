<?php

namespace Tests\Feature;

use App\Models\FinishedGoodStock;
use App\Models\FinishedGoodStockMovement;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockMovement;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\ProductionRealization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_consistent_demo_data_and_is_idempotent(): void
    {
        $this->setAppEnvironment('testing');
        $this->setDatabaseName('erp_demo');
        config([
            'demo.user_password' => 'local-demo-password-from-test',
        ]);

        $this->seed(DatabaseSeeder::class);

        $expectedCounts = [
            'products' => 4,
            'materials' => 10,
            'bom_items' => 18,
            'material_stocks' => 10,
            'material_stock_movements' => 16,
            'production_plans' => 4,
            'production_plan_materials' => 18,
            'production_realizations' => 1,
            'production_realization_materials' => 5,
            'finished_good_stocks' => 1,
            'finished_good_stock_movements' => 1,
            'users' => 0,
        ];

        foreach ($expectedCounts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }

        $this->assertDatabaseMissing('users', ['email' => 'demo@example.com']);
        $this->assertProductAndMaterialDefinitions();
        $this->assertPlanSnapshotsMatchCurrentDemoBom();
        $this->assertRealizationAndFinishedGoodStockAreConsistent();
        $this->assertMaterialStockMatchesMovementHistory();
        $this->assertForeignKeysResolve();

        $stockBeforeRerun = MaterialStock::query()
            ->orderBy('material_id')
            ->pluck('on_hand', 'material_id')
            ->all();
        $movementCountBeforeRerun = MaterialStockMovement::query()->count();

        $this->seed(DatabaseSeeder::class);

        foreach ($expectedCounts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }

        $this->assertSame(
            $stockBeforeRerun,
            MaterialStock::query()->orderBy('material_id')->pluck('on_hand', 'material_id')->all(),
        );
        $this->assertSame($movementCountBeforeRerun, MaterialStockMovement::query()->count());
        $this->assertMaterialStockMatchesMovementHistory();
    }

    public function test_demo_seeder_preserves_existing_demo_account_and_material_balance(): void
    {
        $this->setAppEnvironment('testing');
        $this->setDatabaseName('erp_demo');
        config([
            'demo.user_password' => 'new-demo-password-from-test',
        ]);

        $user = User::factory()->create([
            'name' => 'Existing Demo Owner',
            'email' => 'demo@example.com',
            'password' => 'private-existing-password',
        ]);
        $material = Material::create([
            'sku' => 'MAT-STRAW',
            'name' => 'Existing Straw Material',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $stock = MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '333.25',
            'reorder_level' => '200.00',
        ]);

        $this->seed(DatabaseSeeder::class);

        $user->refresh();
        $stock->refresh();

        $this->assertSame('Existing Demo Owner', $user->name);
        $this->assertTrue(Hash::check('private-existing-password', $user->password));
        $this->assertSame('Existing Straw Material', $material->fresh()->name);
        $this->assertSame('333.25', $stock->on_hand);
        $this->assertSame('200.00', $stock->reorder_level);
        $this->assertDatabaseMissing('material_stock_movements', [
            'material_id' => $material->id,
        ]);
    }

    public function test_local_environment_refuses_sqlite_even_when_database_is_named_erp_demo(): void
    {
        $this->setAppEnvironment('local');
        $this->setDatabaseName('erp_demo');
        config([
            'demo.user_password' => 'local-demo-password-from-test',
        ]);
        $product = Product::create([
            'sku' => 'EXISTING-PRODUCT',
            'name' => 'Must remain untouched',
            'output_unit' => 'pcs',
            'is_active' => true,
        ]);

        $exception = $this->runSeederAndCaptureException();

        $this->assertStringContainsString('hanya boleh menggunakan driver mysql', $exception->getMessage());
        $this->assertDatabaseMissing('users', ['email' => 'demo@example.com']);
        $this->assertDatabaseCount('products', 1);
        $this->assertSame('Must remain untouched', $product->fresh()->name);
    }

    public function test_mysql_erp_demo_local_passes_database_target_validation(): void
    {
        $this->assertDatabaseTargetPasses('local', 'mysql', 'erp_demo');
    }

    public function test_demo_seeder_refuses_to_run_against_development_database_erp(): void
    {
        $product = Product::create([
            'sku' => 'BUSINESS-PRODUCT',
            'name' => 'Existing development product',
            'output_unit' => 'karton',
            'description' => 'Must remain untouched',
            'is_active' => true,
        ]);
        $material = Material::create([
            'sku' => 'BUSINESS-MATERIAL',
            'name' => 'Existing development material',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $stock = MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '123.45',
            'reorder_level' => '50.00',
        ]);
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'private-admin-password',
        ]);

        $exception = $this->assertDatabaseTargetFails('local', 'mysql', 'erp');

        $this->assertStringContainsString('database erp_demo', $exception->getMessage());
        $this->assertStringContainsString('database aktif adalah erp', $exception->getMessage());
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('materials', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Existing development product', $product->fresh()->name);
        $this->assertSame('123.45', $stock->fresh()->on_hand);
        $this->assertSame('50.00', $stock->fresh()->reorder_level);
        $this->assertTrue(Hash::check('private-admin-password', $admin->fresh()->password));
    }

    public function test_sqlite_testing_passes_database_target_validation(): void
    {
        $this->assertDatabaseTargetPasses('testing', 'sqlite', ':memory:');
    }

    public function test_local_environment_rejects_sqlite_database_named_erp_demo_without_changes(): void
    {
        $product = Product::create([
            'sku' => 'EXISTING-PRODUCT',
            'name' => 'Must remain untouched',
            'output_unit' => 'pcs',
            'is_active' => true,
        ]);
        $before = Product::query()->count();

        $exception = $this->assertDatabaseTargetFails('local', 'sqlite', 'erp_demo');

        $this->assertStringContainsString('driver mysql', $exception->getMessage());
        $this->assertSame($before, Product::query()->count());
        $this->assertSame('Must remain untouched', $product->fresh()->name);
    }

    public function test_production_environment_is_rejected_even_for_mysql_erp_demo_without_changes(): void
    {
        $product = Product::create([
            'sku' => 'EXISTING-PRODUCT',
            'name' => 'Must remain untouched',
            'output_unit' => 'pcs',
            'is_active' => true,
        ]);
        $before = Product::query()->count();

        $exception = $this->assertDatabaseTargetFails('production', 'mysql', 'erp_demo');

        $this->assertStringContainsString('APP_ENV=local', $exception->getMessage());
        $this->assertSame($before, Product::query()->count());
        $this->assertSame('Must remain untouched', $product->fresh()->name);
    }

    public function test_demo_account_is_created_only_by_local_account_logic_when_password_is_configured(): void
    {
        $this->setAppEnvironment('local');
        config([
            'demo.user_password' => 'local-demo-password-from-test',
        ]);

        $this->runCreateDemoAccount();

        $this->assertDemoAccountIsSafeAndUsable();
    }

    public function test_local_demo_account_is_skipped_when_password_is_missing(): void
    {
        $this->setAppEnvironment('local');
        config(['demo.user_password' => null]);

        $this->runCreateDemoAccount();

        $this->assertDatabaseMissing('users', ['email' => 'demo@example.com']);
    }

    public function test_preflight_rejects_product_sku_collision_before_any_demo_changes(): void
    {
        $this->setAppEnvironment('testing');
        $this->setDatabaseName('erp_demo');

        $existingProduct = Product::create([
            'sku' => 'DEMO-AF-220',
            'name' => 'Business product',
            'output_unit' => 'pcs',
            'description' => 'Existing business record',
            'is_active' => true,
        ]);
        $admin = User::factory()->create([
            'name' => 'Existing Admin',
            'email' => 'admin@example.com',
            'password' => 'admin-private-password',
        ]);

        $exception = $this->runSeederAndCaptureException();

        $this->assertStringContainsString('SKU produk DEMO-AF-220', $exception->getMessage());
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('materials', 0);
        $this->assertDatabaseMissing('users', ['email' => 'demo@example.com']);
        $this->assertSame('Existing Admin', $admin->fresh()->name);
        $this->assertTrue(Hash::check('admin-private-password', $admin->fresh()->password));
        $this->assertSame($existingProduct->id, Product::query()->value('id'));
    }

    public function test_preflight_rejects_material_sku_collision_before_any_demo_changes(): void
    {
        $this->setAppEnvironment('testing');
        $this->setDatabaseName('erp_demo');

        $existingMaterial = Material::create([
            'sku' => 'DEMO-MAT-CUP-220',
            'name' => 'Business material',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $exception = $this->runSeederAndCaptureException();

        $this->assertStringContainsString('SKU material DEMO-MAT-CUP-220', $exception->getMessage());
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('materials', 1);
        $this->assertSame($existingMaterial->id, Material::query()->value('id'));
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    public function test_preflight_rejects_plan_number_collision_before_any_demo_changes(): void
    {
        $this->setAppEnvironment('testing');
        $this->setDatabaseName('erp_demo');

        $product = Product::create([
            'sku' => 'BUSINESS-1',
            'name' => 'Business product',
            'output_unit' => 'karton',
            'is_active' => true,
        ]);
        ProductionPlan::create([
            'plan_number' => 'DEMO-PP-AF-220-01',
            'product_id' => $product->id,
            'planned_quantity' => '500.00',
            'due_date' => today()->toDateString(),
            'status' => ProductionPlan::STATUS_PLANNED,
        ]);

        $exception = $this->runSeederAndCaptureException();

        $this->assertStringContainsString('Nomor rencana DEMO-PP-AF-220-01', $exception->getMessage());
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('materials', 0);
        $this->assertDatabaseCount('production_plans', 1);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    public function test_preflight_rejects_realization_number_collision_before_any_demo_changes(): void
    {
        $this->setAppEnvironment('testing');
        $this->setDatabaseName('erp_demo');

        $product = Product::create([
            'sku' => 'BUSINESS-1',
            'name' => 'Business product',
            'output_unit' => 'karton',
            'is_active' => true,
        ]);
        ProductionRealization::create([
            'transaction_number' => 'DEMO-RP-AF-1500-01',
            'transaction_date' => today()->toDateString(),
            'product_id' => $product->id,
            'finished_good_quantity' => '10.00',
            'output_unit' => 'karton',
            'status' => ProductionRealization::STATUS_DRAFT,
        ]);

        $exception = $this->runSeederAndCaptureException();

        $this->assertStringContainsString('Nomor realisasi DEMO-RP-AF-1500-01', $exception->getMessage());
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('materials', 0);
        $this->assertDatabaseCount('material_stock_movements', 0);
        $this->assertDatabaseCount('finished_good_stock_movements', 0);
    }

    public function test_preflight_rejects_existing_stock_without_demo_movements(): void
    {
        $this->setAppEnvironment('testing');
        $this->setDatabaseName('erp_demo');

        $material = Material::create([
            'sku' => 'DEMO-MAT-CUP-220',
            'name' => 'Cup 220 ml',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $stock = MaterialStock::create([
            'material_id' => $material->id,
            'on_hand' => '99.00',
            'reorder_level' => '10.00',
        ]);

        $exception = $this->runSeederAndCaptureException();

        $this->assertStringContainsString('tidak memiliki riwayat demo', $exception->getMessage());
        $this->assertSame('99.00', $stock->fresh()->on_hand);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('material_stock_movements', 0);
    }

    private function assertDemoAccountIsSafeAndUsable(): void
    {
        $user = User::query()->where('email', 'demo@example.com')->firstOrFail();

        $this->assertSame('Aqua Fresh Demo', $user->name);
        $this->assertTrue(Hash::check('local-demo-password-from-test', $user->password));
        $this->assertNotSame('local-demo-password-from-test', $user->password);
    }

    private function assertProductAndMaterialDefinitions(): void
    {
        $this->assertDatabaseHas('products', [
            'sku' => 'DEMO-AF-220',
            'name' => 'Aqua Fresh 220 ml',
            'output_unit' => 'karton',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'DEMO-AF-1500',
            'name' => 'Aqua Fresh 1500 ml',
            'output_unit' => 'karton',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('materials', [
            'sku' => 'DEMO-MAT-BOTTLE-600',
            'name' => 'Bottle 600 ml',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('materials', [
            'sku' => 'DEMO-MAT-SEAL',
            'name' => 'Lid / seal cup',
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('material_stocks', [
            'material_id' => Material::query()->where('sku', 'DEMO-MAT-CUP-330')->value('id'),
            'on_hand' => '10000.00',
            'reorder_level' => '11000.00',
        ]);
        $this->assertDatabaseHas('material_stocks', [
            'material_id' => Material::query()->where('sku', 'DEMO-MAT-SHRINK')->value('id'),
            'on_hand' => '440.00',
            'reorder_level' => '500.00',
        ]);

        $this->assertDatabaseHas('bom_items', [
            'product_id' => Product::query()->where('sku', 'DEMO-AF-220')->value('id'),
            'material_id' => Material::query()->where('sku', 'DEMO-MAT-CUP-220')->value('id'),
            'quantity_per_product' => '48.00',
        ]);
        $this->assertDatabaseHas('bom_items', [
            'product_id' => Product::query()->where('sku', 'DEMO-AF-600')->value('id'),
            'material_id' => Material::query()->where('sku', 'DEMO-MAT-CAP')->value('id'),
            'quantity_per_product' => '24.00',
        ]);
    }

    private function assertPlanSnapshotsMatchCurrentDemoBom(): void
    {
        $plans = ProductionPlan::query()->with(['product', 'materials'])->get();
        $this->assertCount(4, $plans);
        $this->assertSame(
            [ProductionPlan::STATUS_PLANNED],
            $plans->pluck('status')->unique()->values()->all(),
        );

        foreach ($plans as $plan) {
            $bom = $plan->product->bomItems()->get()->keyBy('material_id');
            $this->assertCount($bom->count(), $plan->materials);

            foreach ($plan->materials as $snapshot) {
                $bomItem = $bom->get($snapshot->material_id);
                $this->assertNotNull($bomItem);
                $expectedRequired = number_format(
                    (float) $plan->planned_quantity * (float) $bomItem->quantity_per_product,
                    2,
                    '.',
                    '',
                );
                $this->assertSame($expectedRequired, $snapshot->required_quantity);
            }
        }
    }

    private function assertRealizationAndFinishedGoodStockAreConsistent(): void
    {
        $realization = ProductionRealization::query()
            ->with(['product', 'materials', 'finishedGoodMovement'])
            ->where('transaction_number', 'DEMO-RP-AF-1500-01')
            ->firstOrFail();

        $this->assertSame(ProductionRealization::STATUS_COMPLETED, $realization->status);
        $this->assertSame('Aqua Fresh 1500 ml', $realization->product->name);
        $this->assertSame('10.00', $realization->finished_good_quantity);
        $this->assertSame('karton', $realization->output_unit);
        $this->assertCount(5, $realization->materials);

        foreach ($realization->materials as $snapshot) {
            $this->assertSame(
                number_format(
                    (float) $snapshot->quantity_per_output * (float) $realization->finished_good_quantity,
                    2,
                    '.',
                    '',
                ),
                $snapshot->required_quantity,
            );
            $this->assertDatabaseHas('material_stock_movements', [
                'material_id' => $snapshot->material_id,
                'movement_type' => MaterialStockMovement::TYPE_PRODUCTION_CONSUMPTION,
                'quantity' => $snapshot->required_quantity,
                'reference_type' => ProductionRealization::class,
                'reference_id' => $realization->id,
            ]);
        }

        $finishedGoodStock = FinishedGoodStock::query()
            ->where('product_id', $realization->product_id)
            ->firstOrFail();
        $this->assertSame('karton', $finishedGoodStock->unit);
        $this->assertSame('10.00', $finishedGoodStock->on_hand);
        $this->assertInstanceOf(FinishedGoodStockMovement::class, $realization->finishedGoodMovement);
        $this->assertSame('10.00', $realization->finishedGoodMovement->quantity);
    }

    private function assertMaterialStockMatchesMovementHistory(): void
    {
        foreach (Material::query()->whereIn('sku', [
            'DEMO-MAT-CUP-220',
            'DEMO-MAT-CUP-330',
            'DEMO-MAT-BOTTLE-600',
            'DEMO-MAT-BOTTLE-1500',
            'DEMO-MAT-CAP',
            'DEMO-MAT-SEAL',
            'DEMO-MAT-LABEL',
            'DEMO-MAT-CARTON',
            'DEMO-MAT-SHRINK',
            'DEMO-MAT-STRAW',
        ])->get() as $material) {
            $balance = 0;
            $movements = $material->stockMovements()->orderBy('id')->get();

            foreach ($movements as $movement) {
                $quantity = $this->toCents($movement->quantity);

                if ($movement->movement_type === MaterialStockMovement::TYPE_RECEIPT) {
                    $balance += $quantity;
                } elseif ($movement->movement_type === MaterialStockMovement::TYPE_ADJUSTMENT) {
                    $this->assertMatchesRegularExpression(
                        '/saldo ([0-9]+\.[0-9]{2}) menjadi ([0-9]+\.[0-9]{2})/',
                        $movement->notes,
                    );
                    preg_match('/saldo ([0-9]+\.[0-9]{2}) menjadi ([0-9]+\.[0-9]{2})/', $movement->notes, $match);
                    $this->assertSame($balance, $this->toCents($match[1]));
                    $balance = $this->toCents($match[2]);
                } elseif ($movement->movement_type === MaterialStockMovement::TYPE_PRODUCTION_CONSUMPTION) {
                    $balance -= $quantity;
                } else {
                    $this->fail("Unexpected demo stock movement type: {$movement->movement_type}");
                }
            }

            $this->assertGreaterThanOrEqual(0, $balance);
            $this->assertSame(
                $balance,
                $this->toCents($material->stock()->firstOrFail()->on_hand),
                "Stock movements should reconcile with {$material->sku} on-hand balance.",
            );
        }
    }

    private function assertForeignKeysResolve(): void
    {
        $orphanQueries = [
            DB::table('bom_items')->leftJoin('products', 'products.id', '=', 'bom_items.product_id')->whereNull('products.id'),
            DB::table('bom_items')->leftJoin('materials', 'materials.id', '=', 'bom_items.material_id')->whereNull('materials.id'),
            DB::table('material_stocks')->leftJoin('materials', 'materials.id', '=', 'material_stocks.material_id')->whereNull('materials.id'),
            DB::table('material_stock_movements')->leftJoin('materials', 'materials.id', '=', 'material_stock_movements.material_id')->whereNull('materials.id'),
            DB::table('production_plans')->leftJoin('products', 'products.id', '=', 'production_plans.product_id')->whereNull('products.id'),
            DB::table('production_plan_materials')->leftJoin('production_plans', 'production_plans.id', '=', 'production_plan_materials.production_plan_id')->whereNull('production_plans.id'),
            DB::table('production_plan_materials')->leftJoin('materials', 'materials.id', '=', 'production_plan_materials.material_id')->whereNull('materials.id'),
            DB::table('production_realizations')->leftJoin('products', 'products.id', '=', 'production_realizations.product_id')->whereNull('products.id'),
            DB::table('production_realization_materials')->leftJoin('production_realizations', 'production_realizations.id', '=', 'production_realization_materials.production_realization_id')->whereNull('production_realizations.id'),
            DB::table('production_realization_materials')->leftJoin('materials', 'materials.id', '=', 'production_realization_materials.material_id')->whereNull('materials.id'),
            DB::table('finished_good_stocks')->leftJoin('products', 'products.id', '=', 'finished_good_stocks.product_id')->whereNull('products.id'),
            DB::table('finished_good_stock_movements')->leftJoin('products', 'products.id', '=', 'finished_good_stock_movements.product_id')->whereNull('products.id'),
            DB::table('finished_good_stock_movements')->leftJoin('production_realizations', 'production_realizations.id', '=', 'finished_good_stock_movements.production_realization_id')->whereNull('production_realizations.id'),
        ];

        foreach ($orphanQueries as $query) {
            $this->assertSame(0, $query->count());
        }
    }

    private function toCents(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '0');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    private function runSeederAndCaptureException(): RuntimeException
    {
        try {
            app(DemoDataSeeder::class)->run();
        } catch (RuntimeException $exception) {
            return $exception;
        }

        $this->fail('Expected demo seeding preflight to throw a RuntimeException.');
    }

    private function assertDatabaseTargetPasses(string $environment, string $driver, string $databaseName): void
    {
        $seeder = new class extends DemoDataSeeder
        {
            public function validateTarget(string $environment, string $driver, string $databaseName): void
            {
                $this->assertDatabaseTarget($environment, $driver, $databaseName);
            }
        };

        $seeder->validateTarget($environment, $driver, $databaseName);
        $this->addToAssertionCount(1);
    }

    private function assertDatabaseTargetFails(string $environment, string $driver, string $databaseName): RuntimeException
    {
        $seeder = new class extends DemoDataSeeder
        {
            public function validateTarget(string $environment, string $driver, string $databaseName): void
            {
                $this->assertDatabaseTarget($environment, $driver, $databaseName);
            }
        };

        try {
            $seeder->validateTarget($environment, $driver, $databaseName);
        } catch (RuntimeException $exception) {
            return $exception;
        }

        $this->fail('Expected database target validation to throw a RuntimeException.');
    }

    private function runCreateDemoAccount(): void
    {
        $seeder = new class extends DemoDataSeeder
        {
            public function createDemoAccountForTest(): void
            {
                $this->createDemoAccount();
            }
        };

        $seeder->createDemoAccountForTest();
    }

    private function setAppEnvironment(string $environment): void
    {
        $this->app['env'] = $environment;
        config(['app.env' => $environment]);
    }

    private function setDatabaseName(string $databaseName): void
    {
        DB::connection()->setDatabaseName($databaseName);
    }
}

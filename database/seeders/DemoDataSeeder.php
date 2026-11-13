<?php

namespace Database\Seeders;

use App\Models\BomItem;
use App\Models\FinishedGoodStockMovement;
use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\ProductionRealization;
use App\Models\User;
use App\Services\MaterialInventoryService;
use App\Services\MaterialRequirementService;
use App\Services\ProductionRealizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    private const MARKER = '[DEMO:AQUA-FRESH]';

    /**
     * @var array<string, array{name: string, output_unit: string}>
     */
    private const PRODUCTS = [
        'DEMO-AF-220' => ['name' => 'Aqua Fresh 220 ml', 'output_unit' => 'karton'],
        'DEMO-AF-330' => ['name' => 'Aqua Fresh 330 ml', 'output_unit' => 'karton'],
        'DEMO-AF-600' => ['name' => 'Aqua Fresh 600 ml', 'output_unit' => 'karton'],
        'DEMO-AF-1500' => ['name' => 'Aqua Fresh 1500 ml', 'output_unit' => 'karton'],
    ];

    /**
     * @var array<string, array{name: string, unit: string}>
     */
    private const MATERIALS = [
        'DEMO-MAT-CUP-220' => ['name' => 'Cup 220 ml', 'unit' => 'pcs'],
        'DEMO-MAT-CUP-330' => ['name' => 'Cup 330 ml', 'unit' => 'pcs'],
        'DEMO-MAT-BOTTLE-600' => ['name' => 'Bottle 600 ml', 'unit' => 'pcs'],
        'DEMO-MAT-BOTTLE-1500' => ['name' => 'Bottle 1500 ml', 'unit' => 'pcs'],
        'DEMO-MAT-CAP' => ['name' => 'Cap botol', 'unit' => 'pcs'],
        'DEMO-MAT-SEAL' => ['name' => 'Lid / seal cup', 'unit' => 'pcs'],
        'DEMO-MAT-LABEL' => ['name' => 'Label botol', 'unit' => 'pcs'],
        'DEMO-MAT-CARTON' => ['name' => 'Carton box', 'unit' => 'pcs'],
        'DEMO-MAT-SHRINK' => ['name' => 'Shrink film', 'unit' => 'pcs'],
        'DEMO-MAT-STRAW' => ['name' => 'Straw', 'unit' => 'pcs'],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const RECIPES = [
        'DEMO-AF-220' => [
            'DEMO-MAT-CUP-220' => '48.00',
            'DEMO-MAT-SEAL' => '48.00',
            'DEMO-MAT-CARTON' => '1.00',
            'DEMO-MAT-STRAW' => '48.00',
        ],
        'DEMO-AF-330' => [
            'DEMO-MAT-CUP-330' => '48.00',
            'DEMO-MAT-SEAL' => '48.00',
            'DEMO-MAT-CARTON' => '1.00',
            'DEMO-MAT-STRAW' => '48.00',
        ],
        'DEMO-AF-600' => [
            'DEMO-MAT-BOTTLE-600' => '24.00',
            'DEMO-MAT-CAP' => '24.00',
            'DEMO-MAT-LABEL' => '24.00',
            'DEMO-MAT-CARTON' => '1.00',
            'DEMO-MAT-SHRINK' => '1.00',
        ],
        'DEMO-AF-1500' => [
            'DEMO-MAT-BOTTLE-1500' => '24.00',
            'DEMO-MAT-CAP' => '24.00',
            'DEMO-MAT-LABEL' => '24.00',
            'DEMO-MAT-CARTON' => '1.00',
            'DEMO-MAT-SHRINK' => '1.00',
        ],
    ];

    /**
     * @var array<string, array{receipt: string, balance: string, reorder: string}>
     */
    private const OPENING_BALANCES = [
        'DEMO-MAT-CUP-220' => ['receipt' => '30000.00', 'balance' => '30000.00', 'reorder' => '20000.00'],
        'DEMO-MAT-CUP-330' => ['receipt' => '12000.00', 'balance' => '10000.00', 'reorder' => '11000.00'],
        'DEMO-MAT-BOTTLE-600' => ['receipt' => '6500.00', 'balance' => '6500.00', 'reorder' => '7000.00'],
        'DEMO-MAT-BOTTLE-1500' => ['receipt' => '8000.00', 'balance' => '8000.00', 'reorder' => '5000.00'],
        'DEMO-MAT-CAP' => ['receipt' => '15000.00', 'balance' => '15000.00', 'reorder' => '10000.00'],
        'DEMO-MAT-SEAL' => ['receipt' => '40000.00', 'balance' => '40000.00', 'reorder' => '20000.00'],
        'DEMO-MAT-LABEL' => ['receipt' => '7000.00', 'balance' => '7000.00', 'reorder' => '8000.00'],
        'DEMO-MAT-CARTON' => ['receipt' => '1500.00', 'balance' => '1500.00', 'reorder' => '1000.00'],
        'DEMO-MAT-SHRINK' => ['receipt' => '450.00', 'balance' => '450.00', 'reorder' => '500.00'],
        'DEMO-MAT-STRAW' => ['receipt' => '35000.00', 'balance' => '35000.00', 'reorder' => '10000.00'],
    ];

    public function run(): void
    {
        $this->assertDemoDatabaseIsSelected();

        $inventory = app(MaterialInventoryService::class);
        $requirements = app(MaterialRequirementService::class);
        $realizations = app(ProductionRealizationService::class);

        DB::transaction(function () use ($inventory, $requirements, $realizations): void {
            $this->preflight();
            $this->createDemoAccount();

            $products = $this->createProducts();
            $materials = $this->createMaterials();

            $this->createBom($products, $materials);
            $this->createOpeningInventory($materials, $inventory);
            $this->createPlans($products, $requirements);
            $this->createRealization($products['DEMO-AF-1500'], $realizations);
        });
    }

    private function assertDemoDatabaseIsSelected(): void
    {
        $environment = app()->environment();
        $connection = DB::connection();
        $this->assertDatabaseTarget(
            $environment,
            $connection->getDriverName(),
            $connection->getDatabaseName(),
        );
    }

    protected function assertDatabaseTarget(string $environment, string $driver, string $databaseName): void
    {
        if ($environment === 'testing' && $driver === 'sqlite') {
            return;
        }

        if ($environment !== 'local') {
            throw new RuntimeException('DemoDataSeeder hanya boleh dijalankan saat APP_ENV=local.');
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException(
                "DemoDataSeeder pada APP_ENV=local hanya boleh menggunakan driver mysql; driver aktif adalah {$driver}.",
            );
        }

        if ($databaseName !== 'erp_demo') {
            throw new RuntimeException(
                "DemoDataSeeder hanya boleh dijalankan pada database erp_demo; database aktif adalah {$databaseName}.",
            );
        }
    }

    private function preflight(): void
    {
        $products = collect(self::PRODUCTS)->mapWithKeys(function (array $definition, string $sku): array {
            $product = Product::query()->where('sku', $sku)->first();
            if ($product && ! $this->isDemoProduct($product, $sku)) {
                throw new RuntimeException("SKU produk {$sku} sudah digunakan record non-demo; tidak ada data yang diubah.");
            }

            return [$sku => $product];
        });

        $materials = collect(self::MATERIALS)->mapWithKeys(function (array $definition, string $sku): array {
            $material = Material::query()->where('sku', $sku)->first();
            if ($material && ! $this->isDemoMaterial($material, $sku)) {
                throw new RuntimeException("SKU material {$sku} sudah digunakan record non-demo; tidak ada data yang diubah.");
            }

            return [$sku => $material];
        });

        $this->assertBomIsDemoOwned($products, $materials);
        $this->assertMaterialStocksAreDemoOwned($materials);
        $this->assertPlansAreDemoOwned($products, $materials);
        $this->assertRealizationIsDemoOwned($products, $materials);
        $this->assertFinishedGoodStockIsDemoOwned($products);
    }

    /**
     * @param  Collection<string, Product|null>  $products
     * @param  Collection<string, Material|null>  $materials
     */
    private function assertBomIsDemoOwned(Collection $products, Collection $materials): void
    {
        foreach (self::RECIPES as $productSku => $recipe) {
            $product = $products->get($productSku);
            if (! $product) {
                continue;
            }

            foreach ($product->bomItems()->with('material')->get() as $item) {
                $materialSku = $item->material?->sku;
                $expectedQuantity = $recipe[$materialSku] ?? null;
                if (
                    $expectedQuantity === null
                    || $materials->get($materialSku)?->id !== $item->material_id
                    || $item->quantity_per_product !== $expectedQuantity
                ) {
                    throw new RuntimeException("BOM produk {$productSku} berisi data non-demo atau berbeda; tidak ada data yang diubah.");
                }
            }
        }
    }

    /**
     * @param  Collection<string, Material|null>  $materials
     */
    private function assertMaterialStocksAreDemoOwned(Collection $materials): void
    {
        $realization = ProductionRealization::query()
            ->where('transaction_number', 'DEMO-RP-AF-1500-01')
            ->first();

        foreach ($materials as $sku => $material) {
            if (! $material) {
                continue;
            }

            $stock = $material->stock()->first();
            $movements = $material->stockMovements()->orderBy('id')->get();

            if (! $stock && $movements->isNotEmpty()) {
                throw new RuntimeException("Material {$sku} memiliki movements tanpa saldo; preflight demo dihentikan.");
            }

            if ($stock && $movements->isEmpty()) {
                throw new RuntimeException("Saldo material {$sku} tidak memiliki riwayat demo; seeder tidak akan mengubah stok existing.");
            }

            $balance = 0;
            foreach ($movements as $movement) {
                if ($movement->movement_type === MaterialStockMovement::TYPE_RECEIPT) {
                    if (! str_contains((string) $movement->notes, self::MARKER)) {
                        throw new RuntimeException("Material {$sku} memiliki receipt non-demo; seeder tidak akan menggunakan stoknya.");
                    }

                    $balance += $this->toCents($movement->quantity);
                } elseif ($movement->movement_type === MaterialStockMovement::TYPE_ADJUSTMENT) {
                    if (
                        ! str_contains((string) $movement->notes, self::MARKER)
                        || ! preg_match('/saldo ([0-9]+\.[0-9]{2}) menjadi ([0-9]+\.[0-9]{2})/', (string) $movement->notes, $match)
                        || $balance !== $this->toCents($match[1])
                    ) {
                        throw new RuntimeException("Riwayat adjustment material {$sku} bukan milik demo atau tidak konsisten.");
                    }

                    $balance = $this->toCents($match[2]);
                } elseif (
                    $movement->movement_type === MaterialStockMovement::TYPE_PRODUCTION_CONSUMPTION
                    && $realization
                    && $realization->id === $movement->reference_id
                    && $movement->reference_type === ProductionRealization::class
                    && str_contains((string) $movement->notes, 'DEMO-RP-AF-1500-01')
                ) {
                    $balance -= $this->toCents($movement->quantity);
                } else {
                    throw new RuntimeException("Material {$sku} memiliki movement non-demo; seeder dihentikan demi keamanan stok.");
                }
            }

            if ($stock && $balance !== $this->toCents($stock->on_hand)) {
                throw new RuntimeException("Saldo material {$sku} tidak cocok dengan movement history.");
            }
        }
    }

    /**
     * @param  Collection<string, Product|null>  $products
     * @param  Collection<string, Material|null>  $materials
     */
    private function assertPlansAreDemoOwned(Collection $products, Collection $materials): void
    {
        $definitions = [
            ['DEMO-PP-AF-220-01', 'DEMO-AF-220', '500.00'],
            ['DEMO-PP-AF-330-01', 'DEMO-AF-330', '250.00'],
            ['DEMO-PP-AF-600-01', 'DEMO-AF-600', '300.00'],
            ['DEMO-PP-AF-1500-01', 'DEMO-AF-1500', '200.00'],
        ];

        foreach ($definitions as [$number, $productSku, $quantity]) {
            $plan = ProductionPlan::query()->where('plan_number', $number)->first();
            if (! $plan) {
                continue;
            }

            if (
                ! $products->get($productSku)
                || $plan->product_id !== $products->get($productSku)->id
                || $plan->planned_quantity !== $quantity
                || $plan->status !== ProductionPlan::STATUS_PLANNED
            ) {
                throw new RuntimeException("Nomor rencana {$number} digunakan transaksi non-demo; tidak ada data yang diubah.");
            }

            $expected = self::RECIPES[$productSku];
            $snapshots = $plan->materials()->with('material')->get();
            if ($snapshots->count() !== count($expected)) {
                throw new RuntimeException("Snapshot BOM pada rencana {$number} tidak lengkap; preflight dihentikan.");
            }

            foreach ($snapshots as $snapshot) {
                $materialSku = $snapshot->material?->sku;
                $quantityPerProduct = $expected[$materialSku] ?? null;
                $expectedRequired = $quantityPerProduct === null
                    ? null
                    : number_format((float) $quantity * (float) $quantityPerProduct, 2, '.', '');

                if (
                    $expectedRequired === null
                    || $materials->get($materialSku)?->id !== $snapshot->material_id
                    || $snapshot->required_quantity !== $expectedRequired
                ) {
                    throw new RuntimeException("Snapshot BOM pada rencana {$number} tidak sesuai identitas demo.");
                }
            }
        }
    }

    /**
     * @param  Collection<string, Product|null>  $products
     * @param  Collection<string, Material|null>  $materials
     */
    private function assertRealizationIsDemoOwned(Collection $products, Collection $materials): void
    {
        $number = 'DEMO-RP-AF-1500-01';
        $realization = ProductionRealization::query()
            ->where('transaction_number', $number)
            ->first();

        if (! $realization) {
            return;
        }

        if (
            ! $products->get('DEMO-AF-1500')
            || $realization->product_id !== $products->get('DEMO-AF-1500')->id
            || $realization->finished_good_quantity !== '10.00'
            || $realization->output_unit !== 'karton'
            || ! str_contains((string) $realization->notes, self::MARKER)
            || ! in_array($realization->status, [ProductionRealization::STATUS_DRAFT, ProductionRealization::STATUS_COMPLETED], true)
        ) {
            throw new RuntimeException("Nomor realisasi {$number} digunakan transaksi non-demo; tidak ada data yang diubah.");
        }

        $expected = self::RECIPES['DEMO-AF-1500'];
        $snapshots = $realization->materials()->with('material')->get();
        if ($snapshots->count() !== count($expected)) {
            throw new RuntimeException("Snapshot BOM pada realisasi {$number} tidak lengkap.");
        }

        foreach ($snapshots as $snapshot) {
            $materialSku = $snapshot->material?->sku;
            $quantityPerOutput = $expected[$materialSku] ?? null;

            if (
                $quantityPerOutput === null
                || $materials->get($materialSku)?->id !== $snapshot->material_id
                || $snapshot->quantity_per_output !== $quantityPerOutput
                || $snapshot->required_quantity !== number_format((float) $quantityPerOutput * 10, 2, '.', '')
            ) {
                throw new RuntimeException("Snapshot material realisasi {$number} tidak sesuai identitas demo.");
            }
        }

        $consumptionMovements = MaterialStockMovement::query()
            ->where('reference_type', ProductionRealization::class)
            ->where('reference_id', $realization->id)
            ->get()
            ->keyBy('material_id');
        if ($realization->status === ProductionRealization::STATUS_DRAFT && $consumptionMovements->isNotEmpty()) {
            throw new RuntimeException("Realisasi demo {$number} masih draft tetapi sudah memiliki consumption movement.");
        }

        if ($realization->status === ProductionRealization::STATUS_COMPLETED) {
            if ($consumptionMovements->count() !== count($expected)) {
                throw new RuntimeException("Consumption movement realisasi {$number} tidak lengkap.");
            }

            foreach ($snapshots as $snapshot) {
                $movement = $consumptionMovements->get($snapshot->material_id);
                if (! $movement || $movement->quantity !== $snapshot->required_quantity) {
                    throw new RuntimeException("Consumption movement realisasi {$number} tidak cocok dengan snapshot.");
                }
            }

            $finishedMovement = FinishedGoodStockMovement::query()
                ->where('production_realization_id', $realization->id)
                ->first();
            if (
                ! $finishedMovement
                || $finishedMovement->product_id !== $realization->product_id
                || $finishedMovement->quantity !== $realization->finished_good_quantity
                || $finishedMovement->unit !== $realization->output_unit
            ) {
                throw new RuntimeException("Finished goods movement realisasi {$number} tidak konsisten.");
            }
        } elseif (FinishedGoodStockMovement::query()->where('production_realization_id', $realization->id)->exists()) {
            throw new RuntimeException("Realisasi demo {$number} masih draft tetapi sudah memiliki finished goods movement.");
        }
    }

    /**
     * @param  Collection<string, Product|null>  $products
     */
    private function assertFinishedGoodStockIsDemoOwned(Collection $products): void
    {
        foreach ($products as $sku => $product) {
            if (! $product) {
                continue;
            }

            $stock = $product->finishedGoodStock()->first();
            $movements = FinishedGoodStockMovement::query()
                ->where('product_id', $product->id)
                ->get();

            if (! $stock && $movements->isNotEmpty()) {
                throw new RuntimeException("Produk {$sku} memiliki finished goods movement tanpa saldo.");
            }

            if ($stock && $movements->isEmpty()) {
                throw new RuntimeException("Stok barang jadi {$sku} bukan milik demo; seeder tidak akan mengubah saldo existing.");
            }

            $movementRealizationNumbers = ProductionRealization::query()
                ->whereIn('id', $movements->pluck('production_realization_id'))
                ->pluck('transaction_number')
                ->all();

            if (array_diff($movementRealizationNumbers, ['DEMO-RP-AF-1500-01']) !== []) {
                throw new RuntimeException("Produk {$sku} memiliki finished goods movement non-demo.");
            }

            $movementTotal = $movements->sum(fn (FinishedGoodStockMovement $movement): int => $this->toCents($movement->quantity));
            if ($stock && ($stock->unit !== 'karton' || $movementTotal !== $this->toCents($stock->on_hand))) {
                throw new RuntimeException("Saldo finished goods {$sku} tidak cocok dengan movement history.");
            }
        }
    }

    protected function createDemoAccount(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $password = config('demo.user_password');
        if (! is_string($password) || trim($password) === '') {
            $this->command?->warn('Akun demo dilewati: atur DEMO_USER_PASSWORD di environment local untuk membuatnya.');

            return;
        }

        if (User::query()->where('email', 'demo@example.com')->exists()) {
            $this->command?->info('Akun demo@example.com sudah ada dan dibiarkan tanpa perubahan.');

            return;
        }

        User::query()->create([
            'name' => 'Aqua Fresh Demo',
            'email' => 'demo@example.com',
            'password' => $password,
        ]);
    }

    /**
     * @return array<string, Product>
     */
    private function createProducts(): array
    {
        $products = [];
        foreach (self::PRODUCTS as $sku => $definition) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $definition['name'],
                    'output_unit' => $definition['output_unit'],
                    'description' => self::MARKER.' Simulasi produk air mineral.',
                    'is_active' => true,
                ],
            );

            if (! $this->isDemoProduct($product, $sku)) {
                throw new RuntimeException("SKU produk {$sku} berubah setelah preflight; transaksi dibatalkan.");
            }

            $products[$sku] = $product;
        }

        return $products;
    }

    /**
     * @return array<string, Material>
     */
    private function createMaterials(): array
    {
        $materials = [];
        foreach (self::MATERIALS as $sku => $definition) {
            $material = Material::query()->firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $definition['name'],
                    'unit' => $definition['unit'],
                    'is_active' => true,
                ],
            );

            if (! $this->isDemoMaterial($material, $sku)) {
                throw new RuntimeException("SKU material {$sku} berubah setelah preflight; transaksi dibatalkan.");
            }

            $materials[$sku] = $material;
        }

        return $materials;
    }

    /**
     * @param  array<string, Product>  $products
     * @param  array<string, Material>  $materials
     */
    private function createBom(array $products, array $materials): void
    {
        foreach (self::RECIPES as $productSku => $recipe) {
            foreach ($recipe as $materialSku => $quantity) {
                BomItem::query()->firstOrCreate(
                    [
                        'product_id' => $products[$productSku]->id,
                        'material_id' => $materials[$materialSku]->id,
                    ],
                    ['quantity_per_product' => $quantity],
                );
            }
        }
    }

    /**
     * @param  array<string, Material>  $materials
     */
    private function createOpeningInventory(array $materials, MaterialInventoryService $inventory): void
    {
        foreach (self::OPENING_BALANCES as $sku => $quantities) {
            if ($materials[$sku]->stock()->exists()) {
                continue;
            }

            $inventory->receive(
                $materials[$sku],
                $quantities['receipt'],
                self::MARKER.' Opening receipt.',
            );

            if ($quantities['balance'] !== $quantities['receipt']) {
                $inventory->adjustTo(
                    $materials[$sku],
                    $quantities['balance'],
                    self::MARKER.' Opening balance adjustment.',
                );
            }

            $inventory->setReorderLevel($materials[$sku], $quantities['reorder']);
        }
    }

    /**
     * @param  array<string, Product>  $products
     */
    private function createPlans(array $products, MaterialRequirementService $requirements): void
    {
        $definitions = [
            ['DEMO-PP-AF-220-01', 'DEMO-AF-220', '500.00', 7],
            ['DEMO-PP-AF-330-01', 'DEMO-AF-330', '250.00', 14],
            ['DEMO-PP-AF-600-01', 'DEMO-AF-600', '300.00', 21],
            ['DEMO-PP-AF-1500-01', 'DEMO-AF-1500', '200.00', 28],
        ];

        foreach ($definitions as [$number, $productSku, $quantity, $daysUntilDue]) {
            if (ProductionPlan::query()->where('plan_number', $number)->exists()) {
                continue;
            }

            $requirements->createPlannedPlan([
                'product_id' => $products[$productSku]->id,
                'planned_quantity' => $quantity,
                'due_date' => today()->addDays($daysUntilDue)->toDateString(),
            ], $number);
        }
    }

    private function createRealization(Product $product, ProductionRealizationService $realizations): void
    {
        $transactionNumber = 'DEMO-RP-AF-1500-01';
        $existing = ProductionRealization::query()
            ->where('transaction_number', $transactionNumber)
            ->first();

        if ($existing?->status === ProductionRealization::STATUS_COMPLETED) {
            return;
        }

        $realization = $existing ?? $realizations->createDraft([
            'transaction_date' => today()->toDateString(),
            'product_id' => $product->id,
            'finished_good_quantity' => '10.00',
            'notes' => self::MARKER.' Realisasi 10 karton Aqua Fresh 1500 ml.',
        ], $transactionNumber);

        $realizations->post($realization);
    }

    private function isDemoProduct(Product $product, string $sku): bool
    {
        $definition = self::PRODUCTS[$sku];

        return $product->sku === $sku
            && $product->name === $definition['name']
            && $product->output_unit === $definition['output_unit']
            && $product->description === self::MARKER.' Simulasi produk air mineral.'
            && $product->is_active;
    }

    private function isDemoMaterial(Material $material, string $sku): bool
    {
        $definition = self::MATERIALS[$sku];

        return $material->sku === $sku
            && $material->name === $definition['name']
            && $material->unit === $definition['unit']
            && $material->is_active;
    }

    private function toCents(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}

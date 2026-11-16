<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleTransaction;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            'SUP-001' => Supplier::firstOrCreate(['code' => 'SUP-001'], [
                'name' => 'Farma Jaya',
                'company_name' => 'PT Farma Jaya Sehat',
                'address' => 'Jl. Merdeka No. 18, Bandung',
                'phone' => '0227301188',
                'email' => 'distribusi@farmajaya.example',
            ]),
            'SUP-002' => Supplier::firstOrCreate(['code' => 'SUP-002'], [
                'name' => 'Medika Sentosa',
                'company_name' => 'CV Medika Sentosa',
                'address' => 'Jl. Diponegoro No. 42, Jakarta',
                'phone' => '0215552040',
                'email' => 'order@medikasentosa.example',
            ]),
        ];

        $categories = [
            'Analgesik',
            'Vitamin',
            'Antibiotik',
        ];
        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        $medicineData = [
            [
                'code' => 'OBT-0001', 'name' => 'Paracetamol 500 mg', 'category' => 'Analgesik',
                'supplier' => 'SUP-001', 'type' => 'Tablet', 'unit' => 'Tablet',
                'purchase_price' => 800, 'sale_price' => 1500, 'stock' => 42, 'minimum_stock' => 10,
                'expires_at' => now()->addMonths(14)->toDateString(), 'description' => 'Pereda nyeri dan demam.',
            ],
            [
                'code' => 'OBT-0002', 'name' => 'Vitamin C 500 mg', 'category' => 'Vitamin',
                'supplier' => 'SUP-002', 'type' => 'Kaplet', 'unit' => 'Strip',
                'purchase_price' => 1800, 'sale_price' => 3000, 'stock' => 65, 'minimum_stock' => 12,
                'expires_at' => now()->addDays(35)->toDateString(), 'description' => 'Suplemen vitamin C.',
            ],
            [
                'code' => 'OBT-0003', 'name' => 'Amoxicillin 500 mg', 'category' => 'Antibiotik',
                'supplier' => 'SUP-001', 'type' => 'Kapsul', 'unit' => 'Strip',
                'purchase_price' => 4200, 'sale_price' => 6000, 'stock' => 5, 'minimum_stock' => 8,
                'expires_at' => now()->addMonths(10)->toDateString(), 'description' => 'Obat resep.',
            ],
            [
                'code' => 'OBT-0004', 'name' => 'Ibuprofen 200 mg', 'category' => 'Analgesik',
                'supplier' => 'SUP-002', 'type' => 'Tablet', 'unit' => 'Tablet',
                'purchase_price' => 1300, 'sale_price' => 2500, 'stock' => 24, 'minimum_stock' => 6,
                'expires_at' => now()->subDays(5)->toDateString(), 'description' => 'Batch kedaluwarsa untuk demonstrasi peringatan.',
            ],
        ];

        $medicines = [];
        foreach ($medicineData as $data) {
            $supplier = $suppliers[$data['supplier']];
            $medicine = Medicine::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'category_id' => Category::where('name', $data['category'])->value('id'),
                    'supplier_id' => $supplier->id,
                    'type' => $data['type'],
                    'unit' => $data['unit'],
                    'purchase_price' => $data['purchase_price'],
                    'sale_price' => $data['sale_price'],
                    'stock' => $data['stock'],
                    'minimum_stock' => $data['minimum_stock'],
                    'expires_at' => $data['expires_at'],
                    'supplier_name' => $supplier->name,
                    'description' => $data['description'],
                ]
            );
            $medicines[$data['code']] = $medicine;

            StockMovement::firstOrCreate(
                ['medicine_id' => $medicine->id, 'source' => 'opening'],
                [
                    'direction' => 'in',
                    'quantity' => $medicine->stock,
                    'stock_before' => 0,
                    'stock_after' => $medicine->stock,
                    'note' => 'Stok awal data demo.',
                ]
            );
        }

        $this->seedSale(
            'DEMO-TRX-001',
            now()->copy()->setTime(10, 15),
            [
                ['code' => 'OBT-0001', 'quantity' => 2],
                ['code' => 'OBT-0002', 'quantity' => 1],
            ],
            10000,
            $medicines
        );
        $this->seedSale(
            'DEMO-TRX-002',
            now()->copy()->subDay()->setTime(14, 30),
            [['code' => 'OBT-0001', 'quantity' => 1]],
            5000,
            $medicines
        );

        $supplier = $suppliers['SUP-001'];
        $purchaseOrder = PurchaseOrder::firstOrCreate(
            ['order_number' => 'DEMO-PO-001'],
            [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'total' => '42000.00',
                'status' => 'pending',
                'note' => 'Pesanan demo menunggu konfirmasi.',
            ]
        );
        Purchase::firstOrCreate(
            ['purchase_number' => 'DEMO-PO-001-001'],
            [
                'purchase_order_id' => $purchaseOrder->id,
                'medicine_id' => $medicines['OBT-0003']->id,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'quantity' => 10,
                'unit_cost' => '4200.00',
                'status' => 'pending',
                'note' => $purchaseOrder->note,
            ]
        );
    }

    private function seedSale(string $receiptNumber, $soldAt, array $items, int $paidAmount, array $medicines): void
    {
        if (SaleTransaction::where('receipt_number', $receiptNumber)->exists()) {
            return;
        }

        DB::transaction(function () use ($receiptNumber, $soldAt, $items, $paidAmount, $medicines) {
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += (float) $medicines[$item['code']]->sale_price * $item['quantity'];
            }

            $transaction = SaleTransaction::create([
                'receipt_number' => $receiptNumber,
                'subtotal' => $subtotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $paidAmount - $subtotal,
                'sold_at' => $soldAt,
            ]);

            foreach ($items as $index => $itemData) {
                $medicine = Medicine::whereKey($medicines[$itemData['code']]->id)->lockForUpdate()->firstOrFail();
                $quantity = $itemData['quantity'];
                $lineTotal = (float) $medicine->sale_price * $quantity;
                $sale = Sale::create([
                    'sale_transaction_id' => $transaction->id,
                    'sale_number' => $receiptNumber.'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'medicine_id' => $medicine->id,
                    'quantity' => $quantity,
                    'unit_price' => $medicine->sale_price,
                    'unit_cost' => $medicine->purchase_price,
                    'total' => $lineTotal,
                    'sold_at' => $soldAt,
                ]);

                app(StockService::class)->decreaseLocked($medicine, $quantity, $sale);
            }
        });
    }
}
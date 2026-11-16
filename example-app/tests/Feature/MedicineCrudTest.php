<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MedicineCrudTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_medicine_can_be_created_listed_updated_and_deleted(): void
    {
        $category = Category::create(['name' => 'Antibiotik']);
        $supplier = $this->makeSupplier();
        $medicineData = [
            'code' => 'OBT-001',
            'name' => 'Amoxicillin',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Kapsul',
            'unit' => 'Strip',
            'purchase_price' => '12000.00',
            'sale_price' => '15000.00',
            'stock' => 0,
            'minimum_stock' => 10,
            'expires_at' => '2027-12-31',
            'supplier_name' => 'PT Sehat Sentosa',
            'description' => 'Simpan di tempat sejuk.',
        ];

        $this->get(route('medicines.index'))->assertOk();
        $this->get(route('medicines.create'))->assertOk()->assertSee('Pilih kategori');
        $this->post(route('medicines.store'), $medicineData)
            ->assertRedirect(route('medicines.index'));

        $medicine = Medicine::query()->firstOrFail();
        $this->assertDatabaseHas('medicines', [
            'code' => 'OBT-001',
            'name' => 'Amoxicillin',
            'stock' => 0,
        ]);
        $this->get(route('medicines.index'))->assertSee('Amoxicillin');
        $this->get(route('medicines.edit', $medicine))->assertOk();

        $this->put(route('medicines.update', $medicine), array_merge($medicineData, [
            'name' => 'Amoxicillin 500 mg',
        ]))->assertRedirect(route('medicines.index'));
        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'name' => 'Amoxicillin 500 mg']);

        $this->delete(route('medicines.destroy', $medicine))->assertRedirect(route('medicines.index'));
        $this->assertDatabaseMissing('medicines', ['id' => $medicine->id]);
    }

    public function test_medicine_code_must_be_unique_and_stock_cannot_be_negative(): void
    {
        $category = Category::create(['name' => 'Analgesik']);
        $supplier = $this->makeSupplier();
        $medicine = Medicine::create([
            'code' => 'OBT-002',
            'name' => 'Paracetamol',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock' => 10,
            'minimum_stock' => 10,
            'expires_at' => '2027-12-31',
            'supplier_name' => 'PT Sehat Sentosa',
        ]);

        $this->from(route('medicines.create'))
            ->post(route('medicines.store'), [
                'code' => $medicine->code,
                'name' => 'Obat duplikat',
                'category_id' => $category->id,
                'supplier_id' => $supplier->id,
                'type' => 'Tablet',
                'unit' => 'Tablet',
                'purchase_price' => 1000,
                'sale_price' => 1500,
                'stock' => -1,
                'minimum_stock' => 10,
                'expires_at' => '2027-12-31',
                'supplier_name' => 'PT Sehat Sentosa',
            ])
            ->assertRedirect(route('medicines.create'))
            ->assertSessionHasErrors(['code', 'stock']);
    }

    public function test_medicines_can_be_searched_by_name_or_code(): void
    {
        $category = Category::create(['name' => 'Analgesik']);
        $supplier = $this->makeSupplier();
        Medicine::create([
            'code' => 'OBT-101',
            'name' => 'Paracetamol',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock' => 10,
            'expires_at' => '2027-12-31',
            'supplier_name' => 'PT Sehat Sentosa',
        ]);
        Medicine::create([
            'code' => 'OBT-202',
            'name' => 'Ibuprofen',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 2000,
            'sale_price' => 2500,
            'stock' => 8,
            'expires_at' => '2027-12-31',
            'supplier_name' => 'PT Sehat Sentosa',
        ]);

        $this->get(route('medicines.index', ['search' => 'Paracetamol']))
            ->assertOk()
            ->assertSee('Paracetamol')
            ->assertDontSee('Ibuprofen');
        $this->get(route('medicines.index', ['search' => 'OBT-202']))
            ->assertOk()
            ->assertSee('Ibuprofen')
            ->assertDontSee('Paracetamol');
    }

    private function makeSupplier(): Supplier
    {
        return Supplier::create([
            'code' => 'SUP-001',
            'name' => 'Pemasok Tes',
            'company_name' => 'PT Pemasok Tes',
            'address' => 'Jl. Tes No. 1',
            'phone' => '0215550101',
            'email' => 'pemasok@example.test',
        ]);
    }
}
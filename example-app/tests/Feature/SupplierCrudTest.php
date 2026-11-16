<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierCrudTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_supplier_can_be_created_listed_updated_and_deleted(): void
    {
        $supplierData = [
            'code' => 'SUP-001',
            'name' => 'Mitra Farma',
            'company_name' => 'PT Mitra Farma Sejahtera',
            'address' => 'Jl. Kesehatan No. 10, Bandung',
            'phone' => '0221234567',
            'email' => 'mitra@example.test',
        ];

        $this->get(route('suppliers.index'))->assertOk();
        $this->get(route('suppliers.create'))->assertOk();
        $this->post(route('suppliers.store'), $supplierData)->assertRedirect(route('suppliers.index'));

        $supplier = Supplier::query()->firstOrFail();
        $this->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('SUP-001')
            ->assertSee('PT Mitra Farma Sejahtera');
        $this->get(route('suppliers.edit', $supplier))->assertOk();

        $this->put(route('suppliers.update', $supplier), array_merge($supplierData, [
            'name' => 'Mitra Farma Utama',
        ]))->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Mitra Farma Utama']);

        $this->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_code_and_email_must_be_unique(): void
    {
        Supplier::create([
            'code' => 'SUP-002',
            'name' => 'Pemasok Satu',
            'company_name' => 'PT Pemasok Satu',
            'address' => 'Jl. Contoh No. 2',
            'phone' => '0215550102',
            'email' => 'satu@example.test',
        ]);

        $this->from(route('suppliers.create'))
            ->post(route('suppliers.store'), [
                'code' => 'SUP-002',
                'name' => 'Pemasok Duplikat',
                'company_name' => 'PT Pemasok Duplikat',
                'address' => 'Jl. Contoh No. 3',
                'phone' => '0215550103',
                'email' => 'satu@example.test',
            ])
            ->assertRedirect(route('suppliers.create'))
            ->assertSessionHasErrors(['code', 'email']);
    }

    public function test_supplier_in_use_by_a_medicine_cannot_be_deleted(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-003',
            'name' => 'Pemasok Terpakai',
            'company_name' => 'PT Pemasok Terpakai',
            'address' => 'Jl. Contoh No. 4',
            'phone' => '0215550104',
            'email' => 'terpakai@example.test',
        ]);
        $category = Category::create(['name' => 'Kategori Pemasok']);
        Medicine::create([
            'code' => 'OBT-SUP-003',
            'name' => 'Obat Pemasok',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock' => 0,
            'minimum_stock' => 5,
            'expires_at' => '2027-12-31',
            'supplier_name' => $supplier->name,
        ]);

        $this->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
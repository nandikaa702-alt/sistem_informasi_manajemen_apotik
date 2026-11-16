<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryCrudTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created_listed_updated_and_deleted(): void
    {
        $this->get(route('categories.index'))->assertOk();
        $this->get(route('categories.create'))->assertOk();
        $this->post(route('categories.store'), [
            'name' => 'Antibiotik',
            'description' => 'Obat untuk infeksi bakteri.',
        ])->assertRedirect(route('categories.index'));

        $category = Category::query()->firstOrFail();
        $this->get(route('categories.index'))->assertSee('Antibiotik');
    $this->get(route('categories.edit', $category))->assertOk();
        $this->put(route('categories.update', $category), [
            'name' => 'Antibiotik resep',
            'description' => 'Digunakan sesuai petunjuk tenaga kesehatan.',
        ])->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Antibiotik resep']);

        $this->delete(route('categories.destroy', $category))->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::create(['name' => 'Vitamin']);

        $this->from(route('categories.create'))
            ->post(route('categories.store'), ['name' => 'Vitamin'])
            ->assertRedirect(route('categories.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_category_in_use_by_a_medicine_cannot_be_deleted(): void
    {
        $category = Category::create(['name' => 'Analgesik']);
        Medicine::create([
            'code' => 'OBT-301',
            'name' => 'Paracetamol',
            'category_id' => $category->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock' => 10,
            'expires_at' => '2027-12-31',
            'supplier_name' => 'PT Sehat Sentosa',
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
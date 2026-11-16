<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $this->get(route('stock.index'))->assertRedirect(route('login'));
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_cashier_can_sell_and_view_medicines_but_cannot_manage_inventory(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_KASIR]));

        $this->get(route('sales.create'))->assertOk();
        $this->get(route('medicines.index'))->assertOk();
        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('stock.index'))->assertForbidden();
        $this->get(route('purchases.index'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('suppliers.index'))->assertForbidden();
        $this->get(route('medicines.create'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_apothecary_can_manage_operations_but_not_users(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_APOTEKER]));

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('medicines.create'))->assertOk();
        $this->get(route('stock.index'))->assertOk();
        $this->get(route('purchases.index'))->assertOk();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_access_user_management_and_last_admin_is_protected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);
        $this->get(route('users.index'))->assertOk();

        $this->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_APOTEKER,
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('role');

        $this->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => User::ROLE_ADMIN]);
    }
}
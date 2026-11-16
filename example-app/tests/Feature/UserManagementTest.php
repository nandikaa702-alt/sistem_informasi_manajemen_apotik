<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_role_account(): void
    {
        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();
        $this->post(route('users.store'), [
            'name' => 'Petugas Apotek',
            'email' => 'apoteker@example.test',
            'role' => User::ROLE_APOTEKER,
            'password' => 'kata-sandi-aman',
            'password_confirmation' => 'kata-sandi-aman',
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'apoteker@example.test')->firstOrFail();
        $originalPassword = $user->password;
        $this->assertSame(User::ROLE_APOTEKER, $user->role);
        $this->get(route('users.index'))->assertSee('Petugas Apotek')->assertSee('apoteker@example.test');
        $this->get(route('users.edit', $user))->assertOk();

        $this->put(route('users.update', $user), [
            'name' => 'Kasir Utama',
            'email' => 'kasir@example.test',
            'role' => User::ROLE_KASIR,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Kasir Utama', 'role' => User::ROLE_KASIR]);
        $this->assertSame($originalPassword, $user->fresh()->password);

        $this->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
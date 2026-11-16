<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admin_can_be_created_once_through_setup(): void
    {
        $this->get(route('setup'))->assertOk();
        $this->post(route('setup.store'), [
            'name' => 'Admin Pertama',
            'email' => 'admin@example.test',
            'password' => 'password-rahasia-123',
            'password_confirmation' => 'password-rahasia-123',
        ])->assertRedirect(route('login'));

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('password-rahasia-123', $admin->password));
        $this->get(route('setup'))->assertRedirect(route('login'));
        $this->post(route('setup.store'), [
            'name' => 'Admin Kedua',
            'email' => 'admin2@example.test',
            'password' => 'password-rahasia-123',
            'password_confirmation' => 'password-rahasia-123',
        ])->assertNotFound();
    }

    public function test_user_can_login_and_logout(): void
    {
        User::factory()->create([
            'email' => 'kasir@example.test',
            'password' => Hash::make('kata-sandi-aman'),
            'role' => User::ROLE_KASIR,
        ]);

        $this->get(route('login'))->assertOk();
        $this->post(route('login.store'), [
            'email' => 'kasir@example.test',
            'password' => 'kata-sandi-aman',
        ])->assertRedirect(route('sales.create'));
        $this->assertAuthenticated();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $this->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
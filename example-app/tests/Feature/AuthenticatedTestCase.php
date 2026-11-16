<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

abstract class AuthenticatedTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }
}
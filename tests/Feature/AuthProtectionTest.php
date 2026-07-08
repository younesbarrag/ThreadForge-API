<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_current_user_endpoint(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_current_user_endpoint(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_token(): void
    {
        User::factory()->create([
            'email' => 'younes@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'younes@example.com',
            'password' => 'password123',
        ]);

        $response->assertstatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $plainTextToken = $user
            ->createToken('test-token')
            ->plainTextToken;

        $response = $this->withToken($plainTextToken)
            ->postJson('/api/logout');

        $response->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'younes@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'younes@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

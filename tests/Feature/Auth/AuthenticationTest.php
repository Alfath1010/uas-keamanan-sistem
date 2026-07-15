<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.5 (Authentication, FT-AUTH-001..004),
 * FR-001, FR-002
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ft_auth_001_a_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'alice@example.com');

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    }

    #[Test]
    public function duplicate_registration_is_rejected(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Alice Again',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'AUTH003');
    }

    #[Test]
    public function ft_auth_002_a_registered_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'alice@example.com',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'alice@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['uuid', 'token']]);
    }

    #[Test]
    public function ft_auth_004_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'alice@example.com',
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'alice@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'AUTH002');
    }

    #[Test]
    public function ft_auth_003_a_logged_in_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout');

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function unauthenticated_requests_to_protected_endpoints_are_rejected(): void
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(401)->assertJsonPath('error_code', 'AUTH001');
    }
}

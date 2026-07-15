<?php

namespace Tests\Feature\PublicKeys;

use App\Models\User;
use App\Services\Security\ECC\ECCService;
use App\Services\Security\Schnorr\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ActsAsUsers;
use Tests\Support\FakeSchnorrParameterProvider;
use Tests\Support\WithFakeSchnorrParameters;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.5 (Public Keys, FT-KEY-001..003), FR-008
 */
class PublicKeyTest extends TestCase
{
    use RefreshDatabase;
    use ActsAsUsers;
    use WithFakeSchnorrParameters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeSchnorrParameters();
    }

    private function validKeyPayload(): array
    {
        return [
            'ecc_public_key' => (new ECCService())->generateKeyPair()['public_key'],
            'schnorr_public_key' => (new SignatureService(new FakeSchnorrParameterProvider()))
                ->generateKeyPair()['public_key'],
        ];
    }

    #[Test]
    public function ft_key_001_a_user_can_upload_public_keys(): void
    {
        $alice = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->postJson('/api/v1/users/keys', $this->validKeyPayload());

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('public_keys', ['user_id' => $alice->id]);
    }

    #[Test]
    public function ft_key_002_uploading_again_replaces_the_existing_keys(): void
    {
        $alice = User::factory()->create();
        $token = $this->tokenFor($alice);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/users/keys', $this->validKeyPayload())
            ->assertOk();

        $second = $this->validKeyPayload();
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/users/keys', $second)
            ->assertOk();

        $this->assertDatabaseCount('public_keys', 1);
        $this->assertDatabaseHas('public_keys', [
            'user_id' => $alice->id,
            'ecc_public_key' => $second['ecc_public_key'],
        ]);
    }

    #[Test]
    public function ft_key_003_a_users_public_keys_can_be_retrieved(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $payload = $this->validKeyPayload();

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->postJson('/api/v1/users/keys', $payload)
            ->assertOk();

        // Retrieved by a DIFFERENT authenticated user — public keys
        // are meant to be fetchable by any authenticated party.
        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($bob))
            ->getJson("/api/v1/users/{$alice->uuid}/keys");

        $response->assertOk()
            ->assertJsonPath('data.ecc_public_key', $payload['ecc_public_key'])
            ->assertJsonPath('data.schnorr_public_key', $payload['schnorr_public_key']);
    }

    #[Test]
    public function malformed_ecc_key_is_rejected(): void
    {
        $alice = User::factory()->create();
        $payload = $this->validKeyPayload();
        $payload['ecc_public_key'] = base64_encode('too-short');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->postJson('/api/v1/users/keys', $payload);

        $response->assertStatus(422)->assertJsonPath('error_code', 'KEY001');
    }

    #[Test]
    public function unknown_user_uuid_returns_not_found(): void
    {
        $alice = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->getJson('/api/v1/users/'.\Illuminate\Support\Str::uuid().'/keys');

        $response->assertStatus(404)->assertJsonPath('error_code', 'USR001');
    }
}

<?php

namespace Tests\Feature\Conversations;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ActsAsUsers;
use Tests\TestCase;

/**
 * Reference: testing_spec.md §7.5 (Conversations, FT-CONV-001..003), FR-003
 */
class ConversationTest extends TestCase
{
    use RefreshDatabase;
    use ActsAsUsers;

    #[Test]
    public function ft_conv_001_a_user_can_create_a_conversation(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->postJson('/api/v1/conversations', ['recipient_email' => $bob->email]);

        $response->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('conversation_members', ['user_id' => $alice->id]);
        $this->assertDatabaseHas('conversation_members', ['user_id' => $bob->id]);
    }

    #[Test]
    public function creating_a_conversation_with_yourself_is_rejected(): void
    {
        $alice = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->postJson('/api/v1/conversations', ['recipient_email' => $alice->email]);

        $response->assertStatus(422)->assertJsonPath('error_code', 'CONV001');
    }

    #[Test]
    public function ft_conv_002_a_user_can_retrieve_their_conversations(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->postJson('/api/v1/conversations', ['recipient_email' => $bob->email])
            ->assertCreated();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->getJson('/api/v1/conversations');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.conversations'));
    }

    #[Test]
    public function ft_conv_003_unauthorized_access_to_a_conversation_is_denied(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $eve = User::factory()->create(); // not a participant

        $created = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->postJson('/api/v1/conversations', ['recipient_email' => $bob->email])
            ->json('data');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($eve))
            ->getJson("/api/v1/conversations/{$created['uuid']}");

        $response->assertStatus(403)->assertJsonPath('error_code', 'MSG002');
    }

    #[Test]
    public function unknown_conversation_uuid_returns_not_found(): void
    {
        $alice = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($alice))
            ->getJson('/api/v1/conversations/'.\Illuminate\Support\Str::uuid());

        $response->assertStatus(404)->assertJsonPath('error_code', 'MSG001');
    }
}

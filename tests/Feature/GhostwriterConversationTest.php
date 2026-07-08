<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GhostwriterConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_conversation(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/conversations', [
            'title' => 'Improve my Laravel post',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.title',
                'Improve my Laravel post'
            );

        $this->assertDatabaseHas('agent_conversations', [
            'user_id' => $user->id,
            'title' => 'Improve my Laravel post',
        ]);
    }

    public function test_conversation_title_must_be_valid(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/conversations', [
            'title' => str_repeat('a', 256),
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
            ]);
    }

    public function test_user_only_sees_their_own_conversations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownConversation = $this->createConversation(
            $user,
            'My conversation'
        );

        $otherConversation = $this->createConversation(
            $otherUser,
            'Private conversation'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/conversations');

        $response->assertOk();

        $conversationIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains(
            $ownConversation->id,
            $conversationIds
        );

        $this->assertNotContains(
            $otherConversation->id,
            $conversationIds
        );
    }

    public function test_user_can_view_their_own_conversation(): void
    {
        $user = User::factory()->create();

        $conversation = $this->createConversation(
            $user,
            'Laravel discussion'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/conversations/{$conversation->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $conversation->id
            );
    }

    public function test_user_cannot_view_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = $this->createConversation(
            $otherUser,
            'Private conversation'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/conversations/{$conversation->id}"
        );

        $response->assertNotFound();
    }

    public function test_message_requires_valid_content(): void
    {
        $user = User::factory()->create();

        $conversation = $this->createConversation(
            $user,
            'Laravel discussion'
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            []
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'message',
            ]);
    }

    public function test_user_can_delete_their_conversation(): void
    {
        $user = User::factory()->create();

        $conversation = $this->createConversation(
            $user,
            'Conversation to delete'
        );

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            "/api/conversations/{$conversation->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Conversation deleted successfully.'
            );

        $this->assertDatabaseMissing('agent_conversations', [
            'id' => $conversation->id,
        ]);
    }

    private function createConversation(
        User $user,
        string $title
    ): Conversation {
        return Conversation::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => $title,
        ]);
    }
}

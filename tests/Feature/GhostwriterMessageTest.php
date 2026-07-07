<?php

namespace Tests\Feature;

use App\Ai\Agents\Ghostwriter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GhostwriterMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_a_message_and_ai_response_is_saved(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Improve my Laravel post',
        ]);

        Sanctum::actingAs($user);

        Ghostwriter::fake([
            'Voici trois hooks plus directs pour ton post Laravel.',
        ])->preventStrayPrompts();

        $response = $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            [
                'message' => 'Donne-moi trois hooks plus directs.',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'conversation_id',
                $conversation->id
            );

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
        ]);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
        ]);

        $this->assertDatabaseCount(
            'agent_conversation_messages',
            2
        );

        Ghostwriter::assertPrompted(
            function (AgentPrompt $prompt): bool {
                return $prompt->contains(
                    'Donne-moi trois hooks plus directs.'
                );
            }
        );
    }

    public function test_user_cannot_send_a_message_to_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::create([
            'id' => (string) Str::uuid(),
            'user_id' => $otherUser->id,
            'title' => 'Private conversation',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            [
                'message' => 'This message must not be accepted.',
            ]
        );

        $response->assertNotFound();

        $this->assertDatabaseCount(
            'agent_conversation_messages',
            0
        );
    }

    public function test_user_can_continue_the_same_conversation(): void
    {
        $user = User::factory()->create();

        $conversation = Conversation::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Laravel discussion',
        ]);

        Sanctum::actingAs($user);

        Ghostwriter::fake([
            'First assistant response.',
            'Second assistant response.',
        ])->preventStrayPrompts();

        $firstResponse = $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            [
                'message' => 'Give me a stronger hook.',
            ]
        );

        $firstResponse
            ->assertCreated()
            ->assertJsonPath(
                'conversation_id',
                $conversation->id
            );

        $secondResponse = $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            [
                'message' => 'Make the previous suggestion shorter.',
            ]
        );

        $secondResponse
            ->assertCreated()
            ->assertJsonPath(
                'conversation_id',
                $conversation->id
            );

        $this->assertDatabaseCount(
            'agent_conversation_messages',
            4
        );

        $this->assertDatabaseCount(
            'agent_conversations',
            1
        );

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Give me a stronger hook.',
        ]);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Make the previous suggestion shorter.',
        ]);
    }
}
<?php

namespace Tests\Feature;

use App\Enums\ProcessingStatus;
use App\Enums\PublicationStatus;
use App\Models\CampaignBlueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GeneratedPostLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_generated_posts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPost = $this->createGeneratedPost($user);
        $otherPost = $this->createGeneratedPost($otherUser);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/generated-posts');

        $response->assertOk();

        $postIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($ownPost->id, $postIds);
        $this->assertNotContains($otherPost->id, $postIds);
    }

    public function test_user_can_view_their_own_generated_post(): void
    {
        $user = User::factory()->create();
        $generatedPost = $this->createGeneratedPost($user);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/generated-posts/{$generatedPost->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $generatedPost->id);
    }

    public function test_user_cannot_view_another_users_generated_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherPost = $this->createGeneratedPost($otherUser);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/generated-posts/{$otherPost->id}"
        );

        $response->assertNotFound();
    }

    public function test_user_can_update_their_generated_post_status(): void
    {
        $user = User::factory()->create();
        $generatedPost = $this->createGeneratedPost($user);

        Sanctum::actingAs($user);

        $response = $this->patchJson(
            "/api/generated-posts/{$generatedPost->id}/status",
            [
                'publication_status' => 'posted',
            ]
        );

        $response->assertOk();

        $generatedPost->refresh();

        $this->assertSame(
            PublicationStatus::Posted,
            $generatedPost->publication_status
        );

        $this->assertDatabaseHas('generated_posts', [
            'id' => $generatedPost->id,
            'publication_status' => 'posted',
        ]);
    }

    public function test_generated_post_status_must_be_valid(): void
    {
        $user = User::factory()->create();
        $generatedPost = $this->createGeneratedPost($user);

        Sanctum::actingAs($user);

        $response = $this->patchJson(
            "/api/generated-posts/{$generatedPost->id}/status",
            [
                'publication_status' => 'published',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'publication_status',
            ]);

        $this->assertSame(
            PublicationStatus::Draft,
            $generatedPost->refresh()->publication_status
        );
    }

    public function test_user_cannot_update_another_users_generated_post_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherPost = $this->createGeneratedPost($otherUser);

        Sanctum::actingAs($user);

        $response = $this->patchJson(
            "/api/generated-posts/{$otherPost->id}/status",
            [
                'publication_status' => 'archived',
            ]
        );

        $response->assertNotFound();

        $this->assertSame(
            PublicationStatus::Draft,
            $otherPost->refresh()->publication_status
        );
    }

    private function createGeneratedPost(
        User $user,
        array $attributes = []
    ): GeneratedPost {
        $blueprint = CampaignBlueprint::create([
            'user_id' => $user->id,
            'name' => 'Laravel Blueprint '.$user->id,
            'target_audience' => 'Laravel backend developers',
            'tone' => 'Professional',
            'max_hashtags' => 1,
            'max_characters' => 280,
            'additional_rules' => [],
        ]);

        $rawContent = RawContent::create([
            'user_id' => $user->id,
            'campaign_blueprint_id' => $blueprint->id,
            'content' => 'Laravel queues move slow operations to background jobs.',
            'source_type' => 'text',
            'processing_status' => ProcessingStatus::Completed,
        ]);

        return GeneratedPost::create(array_merge([
            'user_id' => $user->id,
            'campaign_blueprint_id' => $blueprint->id,
            'raw_content_id' => $rawContent->id,
            'hook_propose' => 'Make your Laravel API faster with queues.',
            'body_points' => [
                'Move slow work to background jobs.',
                'Return HTTP responses faster.',
            ],
            'technical_readability_score' => 90,
            'suggested_hashtags' => [
                '#Laravel',
            ],
            'tone_compliance_justification' => 'Professional and concise.',
            'raw_payload' => [
                'source' => 'test',
            ],
            'publication_status' => PublicationStatus::Draft,
        ], $attributes));
    }
}
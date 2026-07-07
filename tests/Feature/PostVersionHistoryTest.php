<?php

namespace Tests\Feature;

use App\Enums\ProcessingStatus;
use App\Enums\PublicationStatus;
use App\Models\CampaignBlueprint;
use App\Models\GeneratedPost;
use App\Models\PostVersion;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostVersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_post_versions_from_newest_to_oldest(): void
    {
        $user = User::factory()->create();
        $generatedPost = $this->createGeneratedPost($user);

        PostVersion::create([
            'generated_post_id' => $generatedPost->id,
            'version_number' => 1,
            'hook_propose' => 'First Laravel queues hook.',
            'body_points' => [
                'Move slow tasks to the background.',
            ],
            'suggested_hashtags' => [
                '#Laravel',
            ],
            'tone_compliance_justification' => 'Professional tone.',
            'raw_payload' => [
                'source' => 'test',
            ],
            'source' => 'job',
        ]);

        PostVersion::create([
            'generated_post_id' => $generatedPost->id,
            'version_number' => 2,
            'hook_propose' => 'Improved Laravel queues hook.',
            'body_points' => [
                'Keep API responses fast.',
                'Process heavy work asynchronously.',
            ],
            'suggested_hashtags' => [
                '#Laravel',
                '#Queues',
            ],
            'tone_compliance_justification' => 'Clear and technical tone.',
            'raw_payload' => [
                'source' => 'test',
            ],
            'source' => 'regeneration',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/generated-posts/{$generatedPost->id}/versions"
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version_number', 2)
            ->assertJsonPath('data.0.source', 'regeneration')
            ->assertJsonPath(
                'data.0.hook_propose',
                'Improved Laravel queues hook.'
            )
            ->assertJsonPath('data.1.version_number', 1)
            ->assertJsonPath('data.1.source', 'job');
    }

    public function test_post_version_json_fields_are_returned_as_arrays(): void
    {
        $user = User::factory()->create();
        $generatedPost = $this->createGeneratedPost($user);

        PostVersion::create([
            'generated_post_id' => $generatedPost->id,
            'version_number' => 1,
            'hook_propose' => 'Laravel queues improve API performance.',
            'body_points' => [
                'Move slow work to jobs.',
                'Return responses quickly.',
            ],
            'suggested_hashtags' => [
                '#Laravel',
            ],
            'tone_compliance_justification' => 'Concise and professional.',
            'raw_payload' => [
                'provider' => 'fake',
                'source' => 'test',
            ],
            'source' => 'job',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/generated-posts/{$generatedPost->id}/versions"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.0.body_points', [
                'Move slow work to jobs.',
                'Return responses quickly.',
            ])
            ->assertJsonPath('data.0.suggested_hashtags', [
                '#Laravel',
            ])
            ->assertJsonPath('data.0.raw_payload.source', 'test');
    }

    public function test_user_cannot_view_another_users_post_versions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherPost = $this->createGeneratedPost($otherUser);

        PostVersion::create([
            'generated_post_id' => $otherPost->id,
            'version_number' => 1,
            'hook_propose' => 'Private post version.',
            'body_points' => [
                'Private content.',
            ],
            'suggested_hashtags' => [],
            'tone_compliance_justification' => 'Private.',
            'raw_payload' => [
                'source' => 'test',
            ],
            'source' => 'job',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/generated-posts/{$otherPost->id}/versions"
        );

        $response->assertNotFound();
    }

    private function createGeneratedPost(User $user): GeneratedPost
    {
        $blueprint = CampaignBlueprint::create([
            'user_id' => $user->id,
            'name' => 'Version Test Blueprint '.$user->id,
            'target_audience' => 'Laravel backend developers',
            'tone' => 'Professional',
            'max_hashtags' => 2,
            'max_characters' => 280,
            'additional_rules' => [],
        ]);

        $rawContent = RawContent::create([
            'user_id' => $user->id,
            'campaign_blueprint_id' => $blueprint->id,
            'content' => 'Laravel queues allow heavy operations to run in background jobs.',
            'source_type' => 'text',
            'processing_status' => ProcessingStatus::Completed,
        ]);

        return GeneratedPost::create([
            'user_id' => $user->id,
            'campaign_blueprint_id' => $blueprint->id,
            'raw_content_id' => $rawContent->id,
            'hook_propose' => 'Build faster Laravel APIs with queues.',
            'body_points' => [
                'Move heavy tasks to jobs.',
            ],
            'technical_readability_score' => 90,
            'suggested_hashtags' => [
                '#Laravel',
            ],
            'tone_compliance_justification' => 'Professional and technical.',
            'raw_payload' => [
                'source' => 'test',
            ],
            'publication_status' => PublicationStatus::Draft,
        ]);
    }
}
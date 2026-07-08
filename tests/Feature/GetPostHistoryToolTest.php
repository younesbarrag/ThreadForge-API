<?php

namespace Tests\Feature;

use App\Ai\Tools\GetPostHistoryTool;
use App\Enums\ProcessingStatus;
use App\Enums\PublicationStatus;
use App\Models\CampaignBlueprint;
use App\Models\GeneratedPost;
use App\Models\PostVersion;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request as ToolRequest;
use Tests\TestCase;

class GetPostHistoryToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_tool_returns_owned_post_history_from_newest_to_oldest(): void
    {
        $user = User::factory()->create();

        $generatedPost = $this->createGeneratedPost($user);

        PostVersion::create([
            'generated_post_id' => $generatedPost->id,
            'version_number' => 1,
            'hook_propose' => 'First hook.',
            'body_points' => [
                'First body point.',
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
            'hook_propose' => 'Improved hook.',
            'body_points' => [
                'Improved body point.',
            ],
            'suggested_hashtags' => [
                '#Laravel',
                '#Queues',
            ],
            'tone_compliance_justification' => 'Clear and technical.',
            'raw_payload' => [
                'source' => 'test',
            ],
            'source' => 'regeneration',
        ]);

        $tool = new GetPostHistoryTool($user->id);

        $json = $tool->handle(
            new ToolRequest([
                'post_id' => $generatedPost->id,
            ])
        );

        $result = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            $generatedPost->id,
            $result['post_id']
        );

        $this->assertSame(
            'Build faster Laravel APIs with queues.',
            $result['current_hook']
        );

        $this->assertSame('draft', $result['publication_status']);
        $this->assertSame(2, $result['total_versions']);

        $this->assertSame(
            2,
            $result['versions'][0]['version_number']
        );

        $this->assertSame(
            'Improved hook.',
            $result['versions'][0]['hook_propose']
        );

        $this->assertSame(
            1,
            $result['versions'][1]['version_number']
        );
    }

    public function test_tool_does_not_return_another_users_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherPost = $this->createGeneratedPost($otherUser);

        $tool = new GetPostHistoryTool($user->id);

        $json = $tool->handle(
            new ToolRequest([
                'post_id' => $otherPost->id,
            ])
        );

        $result = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'Generated Post not found or not owned by the user.',
            $result['error']
        );
    }

    private function createGeneratedPost(User $user): GeneratedPost
    {
        $blueprint = CampaignBlueprint::create([
            'user_id' => $user->id,
            'name' => 'History Blueprint '.$user->id,
            'target_audience' => 'Laravel backend developers',
            'tone' => 'Professional',
            'max_hashtags' => 2,
            'max_characters' => 280,
            'additional_rules' => [],
        ]);

        $rawContent = RawContent::create([
            'user_id' => $user->id,
            'campaign_blueprint_id' => $blueprint->id,
            'content' => 'Laravel queues execute heavy operations in background jobs.',
            'source_type' => 'text',
            'processing_status' => ProcessingStatus::Completed,
        ]);

        return GeneratedPost::create([
            'user_id' => $user->id,
            'campaign_blueprint_id' => $blueprint->id,
            'raw_content_id' => $rawContent->id,
            'hook_propose' => 'Build faster Laravel APIs with queues.',
            'body_points' => [
                'Move heavy tasks to background jobs.',
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

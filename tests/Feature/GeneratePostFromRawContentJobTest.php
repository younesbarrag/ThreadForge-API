<?php

namespace Tests\Feature;

use App\Ai\Agents\PostGenerator;
use App\Enums\ProcessingStatus;
use App\Jobs\GeneratePostFromRawContentJob;
use App\Models\CampaignBlueprint;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class GeneratePostFromRawContentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_generates_and_stores_a_post_using_ai(): void
    {
        $user = User::factory()->create();

        $blueprint = CampaignBlueprint::create([
            'user_id' => $user->id,
            'name' => 'Laravel Blueprint',
            'target_audience' => 'Laravel backend developers',
            'tone' => 'Professional',
            'max_hashtags' => 1,
            'max_characters' => 280,
            'additional_rules' => [
                'Use concise sentences',
            ],
        ]);

        $rawContent = RawContent::create([
            'user_id' => $user->id,
            'campaign_blueprint_id' => $blueprint->id,
            'content' => 'Laravel queues allow slow tasks to run asynchronously in the background.',
            'source_type' => 'text',
            'processing_status' => ProcessingStatus::Pending,
        ]);

        PostGenerator::fake([
            [
                'hook_proposal' => 'Stop blocking your Laravel requests with slow tasks.',
                'body_points' => [
                    'Move heavy work to queued jobs.',
                    'Return responses faster to users.',
                ],
                'technical_readability_score' => 90,
                'suggested_hashtags' => [
                    '#Laravel',
                ],
                'tone_compliance_justification' => 'The content is concise, technical, and professional.',
            ],
        ])->preventStrayPrompts();

        $job = new GeneratePostFromRawContentJob($rawContent->id);

        app()->call([$job, 'handle']);

        $rawContent->refresh();

        $this->assertSame(
            ProcessingStatus::Completed,
            $rawContent->processing_status
        );

        $this->assertNull($rawContent->error_message);

        $generatedPost = GeneratedPost::query()
            ->where('raw_content_id', $rawContent->id)
            ->first();

        $this->assertNotNull($generatedPost);

        $this->assertSame(
            'Stop blocking your Laravel requests with slow tasks.',
            $generatedPost->hook_propose
        );

        $this->assertSame(
            [
                'Move heavy work to queued jobs.',
                'Return responses faster to users.',
            ],
            $generatedPost->body_points
        );

        $this->assertSame(
            90,
            $generatedPost->technical_readability_score
        );

        $this->assertSame(
            ['#Laravel'],
            $generatedPost->suggested_hashtags
        );

        $this->assertDatabaseHas('post_versions', [
            'generated_post_id' => $generatedPost->id,
            'version_number' => 1,
            'source' => 'job',
        ]);

        PostGenerator::assertPrompted(
            function (AgentPrompt $prompt): bool {
                return $prompt->contains('Laravel queues')
                    && $prompt->contains('Laravel Blueprint');
            }
        );
    }
}

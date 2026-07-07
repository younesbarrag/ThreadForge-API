<?php

namespace Tests\Feature;

use App\Enums\ProcessingStatus;
use App\Jobs\GeneratePostFromRawContentJob;
use App\Models\CampaignBlueprint;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RawContentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_content_submission_requires_valid_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        Queue::fake();

        $response = $this->postJson('/api/raw-contents', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'campaign_blueprint_id',
                'content',
                'source_type',
            ]);

        Queue::assertNothingPushed();
    }

    public function test_authenticated_user_can_submit_raw_content_for_async_generation(): void
    {
        $user = User::factory()->create();

        $blueprint = CampaignBlueprint::create([
            'user_id' => $user->id,
            'name' => 'Laravel Content',
            'target_audience' => 'Laravel backend developers',
            'tone' => 'Professional',
            'max_hashtags' => 1,
            'max_characters' => 280,
            'additional_rules' => [],
        ]);

        Sanctum::actingAs($user);
        Queue::fake();

        $response = $this->postJson('/api/raw-contents', [
            'campaign_blueprint_id' => $blueprint->id,
            'content' => 'Laravel queues allow slow operations to run asynchronously in the background.',
            'source_type' => 'text',
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath(
                'message',
                'Raw content accepted for async generation.'
            );

        $rawContent = RawContent::query()->first();

        $this->assertNotNull($rawContent);
        $this->assertSame($user->id, $rawContent->user_id);
        $this->assertSame(
            $blueprint->id,
            $rawContent->campaign_blueprint_id
        );
        $this->assertSame(
            ProcessingStatus::Pending,
            $rawContent->processing_status
        );

        Queue::assertPushed(
            GeneratePostFromRawContentJob::class,
            function (GeneratePostFromRawContentJob $job) use ($rawContent): bool {
                return $job->rawContentId === $rawContent->id;
            }
        );
    }

    public function test_user_cannot_submit_content_using_another_users_blueprint(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherBlueprint = CampaignBlueprint::create([
            'user_id' => $otherUser->id,
            'name' => 'Private Blueprint',
            'target_audience' => 'Tech creators',
            'tone' => 'Casual',
            'max_hashtags' => 2,
            'max_characters' => 250,
            'additional_rules' => [],
        ]);

        Sanctum::actingAs($user);
        Queue::fake();

        $response = $this->postJson('/api/raw-contents', [
            'campaign_blueprint_id' => $otherBlueprint->id,
            'content' => 'This content is long enough to pass the minimum content validation.',
            'source_type' => 'text',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'campaign_blueprint_id',
            ]);

        $this->assertDatabaseCount('raw_contents', 0);

        Queue::assertNothingPushed();
    }
}
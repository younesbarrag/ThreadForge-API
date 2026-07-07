<?php

namespace Tests\Feature;

use App\Ai\Tools\GetCampaignRulesTool;
use App\Models\CampaignBlueprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request as ToolRequest;
use Tests\TestCase;

class GetCampaignRulesToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_tool_returns_campaign_rules_owned_by_the_user(): void
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

        $tool = new GetCampaignRulesTool($user->id);

        $json = $tool->handle(
            new ToolRequest([
                'campaign_id' => $blueprint->id,
            ])
        );

        $result = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame($blueprint->id, $result['id']);
        $this->assertSame('Laravel Blueprint', $result['name']);
        $this->assertSame(
            'Laravel backend developers',
            $result['target_audience']
        );
        $this->assertSame('Professional', $result['tone']);
        $this->assertSame(1, $result['max_hashtags']);
        $this->assertSame(280, $result['max_characters']);
        $this->assertSame(
            ['Use concise sentences'],
            $result['additional_rules']
        );
    }

    public function test_tool_does_not_return_another_users_campaign(): void
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

        $tool = new GetCampaignRulesTool($user->id);

        $json = $tool->handle(
            new ToolRequest([
                'campaign_id' => $otherBlueprint->id,
            ])
        );

        $result = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'Campaign Blueprint not found or not owned by the user.',
            $result['error']
        );
    }
}
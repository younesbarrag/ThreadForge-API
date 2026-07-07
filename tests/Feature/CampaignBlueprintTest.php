<?php

namespace Tests\Feature;

use App\Models\CampaignBlueprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignBlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_blueprint_creation_requires_valid_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/blueprints', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'tone',
                'max_hashtags',
                'max_characters',
            ]);
    }

    public function test_authenticated_user_can_create_a_blueprint(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/blueprints', [
            'name' => 'Laravel Technical Posts',
            'target_audience' => 'Laravel backend developers',
            'tone' => 'Professional but relaxed',
            'max_hashtags' => 1,
            'max_characters' => 280,
            'additional_rules' => [
                'Avoid marketing language',
                'Use concise sentences',
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('campaign_blueprints', [
            'user_id' => $user->id,
            'name' => 'Laravel Technical Posts',
            'tone' => 'Professional but relaxed',
            'max_hashtags' => 1,
            'max_characters' => 280,
        ]);
    }

    public function test_user_only_sees_their_own_blueprints(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBlueprint = CampaignBlueprint::create([
            'user_id' => $user->id,
            'name' => 'My Blueprint',
            'target_audience' => 'Backend developers',
            'tone' => 'Professional',
            'max_hashtags' => 1,
            'max_characters' => 280,
            'additional_rules' => [],
        ]);

        $otherBlueprint = CampaignBlueprint::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Blueprint',
            'target_audience' => 'Frontend developers',
            'tone' => 'Casual',
            'max_hashtags' => 2,
            'max_characters' => 250,
            'additional_rules' => [],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/blueprints');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $ownBlueprint->id,
                'name' => 'My Blueprint',
            ])
            ->assertJsonMissing([
                'id' => $otherBlueprint->id,
                'name' => 'Other User Blueprint',
            ]);
    }

    public function test_guest_cannot_access_blueprints(): void
    {
        $response = $this->getJson('/api/blueprints');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_blueprints_with_correct_json_structure(): void
    {
        $user = User::factory()->create();

        $blueprint = CampaignBlueprint::create([
            'user_id' => $user->id,
            'name' => 'CI Blueprint',
            'target_audience' => 'Laravel backend developers',
            'tone' => 'Professional',
            'max_hashtags' => 1,
            'max_characters' => 280,
            'additional_rules' => [
                'Use concise sentences',
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/blueprints');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'tone',
                    ],
                ],
            ])
            ->assertJsonPath('data.0.id', $blueprint->id)
            ->assertJsonPath('data.0.name', 'CI Blueprint')
            ->assertJsonPath('data.0.tone', 'Professional');
    }
}

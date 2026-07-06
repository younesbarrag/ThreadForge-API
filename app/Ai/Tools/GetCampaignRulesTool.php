<?php

namespace App\Ai\Tools;

use App\Models\CampaignBlueprint;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetCampaignRulesTool implements Tool
{
    public function __construct(private int $userId) {}

    public function description(): string
    {
        return 'Retrieve the style rules and constraints of a Campaign Blueprint by its ID. Use this to know the tone, max hashtags, max characters, target audience, and any additional rules for content generation.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'campaign_id' => $schema
                ->integer()
                ->description('The ID of the Campaign Blueprint to retrieve')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $campaignId = (int) $request['campaign_id'];

        $blueprint = CampaignBlueprint::where('id', $campaignId)
            ->where('user_id', $this->userId)
            ->first();

        if (! $blueprint) {
            return json_encode([
                'error' => 'Campaign Blueprint not found or not owned by the user.',
            ]);
        }

        return json_encode([
            'id' => $blueprint->id,
            'name' => $blueprint->name,
            'target_audience' => $blueprint->target_audience,
            'tone' => $blueprint->tone,
            'max_hashtags' => $blueprint->max_hashtags,
            'max_characters' => $blueprint->max_characters,
            'additional_rules' => $blueprint->additional_rules,
        ], JSON_THROW_ON_ERROR);
    }
}

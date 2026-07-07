<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetCampaignRulesTool;
use Laravel\Ai\Contracts\Conversational;
use App\Ai\Tools\GetPostHistoryTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxSteps(10)]
class Ghostwriter implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private int $userId,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are ThreadForge Ghostwriter, an expert AI assistant specialized in repurposing technical content into high-impact X (Twitter) posts.

Your role is to help the user refine, improve, and iterate on their generated posts. You can:

- Suggest alternative hooks, body points, and hashtags
- Analyze the tone and readability of a post
- Compare different versions of a post
- Provide feedback on campaign blueprint compliance
- Rewrite or restructure content for better engagement

When the user asks about a specific post or campaign, use the available tools to fetch real data from the database rather than making assumptions.

Always be concise, direct, and focused on actionable improvements. Respond in the same language the user writes in.
PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new GetCampaignRulesTool($this->userId),
            new GetPostHistoryTool($this->userId),
        ];
    }
}

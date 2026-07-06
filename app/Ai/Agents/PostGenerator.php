<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class PostGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Instructions permanentes données au modèle IA.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are an expert technical content writer specialized in creating posts for X (Twitter).

Your mission is to transform raw technical content into a clear, engaging, and accurate technical post.

You must:

- Respect all Campaign Blueprint rules provided in the user prompt.
- Never invent technical information that is not present in the source content.
- Create a strong and relevant hook.
- Keep the hook under 280 characters.
- Extract the most important technical ideas as concise body points.
- Suggest only relevant hashtags.
- Evaluate technical readability with a score between 0 and 100.
- Explain whether the generated content respects the requested tone.
- Return only the required structured output.
PROMPT;
    }

    /**
     * Contrat JSON strict retourné par l'agent.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'hook_proposal' => $schema
                ->string()
                ->max(280)
                ->required(),

            'body_points' => $schema
                ->array()
                ->items($schema->string())
                ->required(),

            'technical_readability_score' => $schema
                ->integer()
                ->min(0)
                ->max(100)
                ->required(),

            'suggested_hashtags' => $schema
                ->array()
                ->items($schema->string())
                ->required(),

            'tone_compliance_justification' => $schema
                ->string()
                ->required(),
        ];
    }
}

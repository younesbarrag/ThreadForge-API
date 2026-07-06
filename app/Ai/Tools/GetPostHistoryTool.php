<?php

namespace App\Ai\Tools;

use App\Models\GeneratedPost;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetPostHistoryTool implements Tool
{
    public function __construct(private int $userId) {}

    public function description(): string
    {
        return 'Retrieve the full version history of a Generated Post by its ID. Includes all previous versions with their hooks, body points, hashtags, and tone justification.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema
                ->integer()
                ->description('The ID of the Generated Post to retrieve history for')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $postId = (int) $request['post_id'];

        $post = GeneratedPost::where('id', $postId)
            ->where('user_id', $this->userId)
            ->with(['versions' => fn ($q) => $q->orderByDesc('version_number')])
            ->first();

        if (! $post) {
            return json_encode([
                'error' => 'Generated Post not found or not owned by the user.',
            ]);
        }

        $versions = $post->versions->map(fn ($v) => [
            'version_number' => $v->version_number,
            'hook_propose' => $v->hook_propose,
            'body_points' => $v->body_points,
            'suggested_hashtags' => $v->suggested_hashtags,
            'tone_compliance_justification' => $v->tone_compliance_justification,
            'source' => $v->source,
            'created_at' => $v->created_at?->toISOString(),
        ]);

        return json_encode([
            'post_id' => $post->id,
            'current_hook' => $post->hook_propose,
            'current_body_points' => $post->body_points,
            'current_hashtags' => $post->suggested_hashtags,
            'publication_status' => $post->publication_status->value,
            'total_versions' => $versions->count(),
            'versions' => $versions->toArray(),
        ], JSON_THROW_ON_ERROR);
    }
}

<?php

namespace App\Jobs;

use App\Enums\ProcessingStatus;
use App\Enums\PublicationStatus;
use App\Models\GeneratedPost;
use App\Models\RawContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GeneratePostFromRawContentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $rawContentId)
    {
        //
    }

    public function handle(): void
    {
        $rawContent = RawContent::with('campaignBlueprint')->find($this->rawContentId);

        if (! $rawContent) {
            return;
        }

        $rawContent->update([
            'processing_status' => ProcessingStatus::Processing,
            'error_message' => null,
        ]);

        DB::transaction(function () use ($rawContent) {
            $blueprint = $rawContent->campaignBlueprint;

            $maxHashtags = $blueprint?->max_hashtags ?? 3;
            $maxCharacters = $blueprint?->max_characters ?? 280;

            $hashtags = array_slice([
                '#Laravel',
                '#API',
                '#Backend',
                '#Queues',
                '#AI',
            ], 0, $maxHashtags);

            $hook = Str::limit(
                '🚀 ' . $rawContent->content,
                $maxCharacters
            );

            GeneratedPost::updateOrCreate(
                [
                    'raw_content_id' => $rawContent->id,
                ],
                [
                    'user_id' => $rawContent->user_id,
                    'campaign_blueprint_id' => $rawContent->campaign_blueprint_id,
                    'hook_propose' => $hook,
                    'body_points' => [
                        'Laravel Queues help move slow tasks to the background.',
                        'The API returns quickly without waiting for heavy processing.',
                        'This structure is useful for future AI generation.',
                    ],
                    'technical_readability_score' => 85,
                    'suggested_hashtags' => $hashtags,
                    'tone_compliance_justification' => 'The generated post follows the selected campaign tone and keeps the content simple, technical, and educational.',
                    'raw_payload' => [
                        'source' => 'mock_generation',
                        'note' => 'This is a temporary mock output before integrating the AI provider.',
                    ],
                    'publication_status' => PublicationStatus::Draft,
                ]
            );

            $rawContent->update([
                'processing_status' => ProcessingStatus::Completed,
            ]);
        });
    }

    public function failed(Throwable $exception): void
    {
        $rawContent = RawContent::find($this->rawContentId);

        if (! $rawContent) {
            return;
        }

        $rawContent->update([
            'processing_status' => ProcessingStatus::Failed,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
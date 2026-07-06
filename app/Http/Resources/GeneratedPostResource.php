<?php

namespace App\Http\Resources;

use App\Enums\ProcessingStatus;
use App\Enums\PublicationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class GeneratedPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'campaign_blueprint_id' => $this->campaign_blueprint_id,
            'campaign_blueprint' => $this->whenLoaded('campaignBlueprint', function () {
                return [
                    'id' => $this->campaignBlueprint->id,
                    'name' => $this->campaignBlueprint->name,
                    'tone' => $this->campaignBlueprint->tone,
                ];
            }),

            'raw_content_id' => $this->raw_content_id,
            'raw_content' => $this->whenLoaded('rawContent', function () {
                $processingStatus = $this->rawContent->processing_status;

                return [
                    'id' => $this->rawContent->id,
                    'content_preview' => Str::limit($this->rawContent->content, 120),
                    'source_type' => $this->rawContent->source_type,
                    'processing_status' => $processingStatus instanceof ProcessingStatus
                        ? $processingStatus->value
                        : $processingStatus,
                ];
            }),

            'hook_propose' => $this->hook_propose,
            'body_points' => $this->body_points,
            'technical_readability_score' => $this->technical_readability_score,
            'suggested_hashtags' => $this->suggested_hashtags,
            'tone_compliance_justification' => $this->tone_compliance_justification,
            'raw_payload' => $this->raw_payload,

            'publication_status' => $this->publication_status instanceof PublicationStatus
                ? $this->publication_status->value
                : $this->publication_status,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

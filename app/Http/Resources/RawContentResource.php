<?php

namespace App\Http\Resources;

use App\Enums\ProcessingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class RawContentResource extends JsonResource
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
                ];
            }),
            'content_preview' => Str::limit($this->content, 120),
            'source_type' => $this->source_type,
            'processing_status' => $this->processing_status instanceof ProcessingStatus
                ? $this->processing_status->value
                : $this->processing_status,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

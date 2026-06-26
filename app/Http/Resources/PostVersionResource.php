<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this ->id,
            'generated_post_id' => $this->generated_post_id,
            'version_number' => $this -> version_number,
            'hook_propose' => $this ->hook_propose,
            'body_points' => $this ->body_points,
            'suggested_hashtags' => $this -> suggested_hashtags,
            'tone_compliance_justification' =>$this->tone_compliance_justification,
            'raw_payload'=> $this->raw_payload,
            'source' => $this->source,
            'created_at' =>$this->created_at,
            'updated_at'=>$this->updated_at,

        ];
    }
}

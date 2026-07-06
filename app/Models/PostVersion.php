<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostVersion extends Model
{
    protected $fillable = [
        'generated_post_id',
        'version_number',
        'hook_propose',
        'body_points',
        'suggested_hashtags',
        'tone_compliance_justification',
        'raw_payload',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'body_points' => 'array',
            'suggested_hashtags' => 'array',
            'raw_payload' => 'array',
        ];
    }

    public function generatedPost(): BelongsTo
    {
        return $this->belongsTo(GeneratedPost::class);
    }
}

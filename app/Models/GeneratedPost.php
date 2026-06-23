<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\PublicationStatus;

class GeneratedPost extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_blueprint_id',
        'raw_content_id',
        'hook_propose',
        'body_points',
        'technical_readability_score',
        'suggested_hashtags',
        'tone_compliance_justification',
        'raw_payload',
        'publication_status',

    ];
    protected function casts(): array
    {
        return [
            'body_points' => 'array',
            'suggested_hashtags' => 'array',
            'raw_payload' => 'array',
            'publication_status' => PublicationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaignBlueprint(): BelongsTo
    {
        return $this->belongsTo(CampaignBlueprint::class);
    }

    public function rawContent(): BelongsTo
    {
        return $this->belongsTo(RawContent::class);
    }
}

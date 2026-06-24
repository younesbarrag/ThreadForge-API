<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;


class CampaignBlueprint extends Model
{
   protected $fillable = [
    'user_id',
    'name',
    'target_audience',
    'tone',
    'max_hashtags',
    'max_characters',
    'additional_rules',
];

protected function casts(): array
{
    return [
        'additional_rules' => 'array',
    ];
}

   public function user(): BelongsTo
   {
    return $this->belongsTo(User::class);
   }


   public function rawcontents(): HasMany
   {
    return $this->hasMany(RawContent::class);
   }

   public function generatedposts(): HasMany
   {
    return $this->hasMany(GeneratedPost::class);
   }
}

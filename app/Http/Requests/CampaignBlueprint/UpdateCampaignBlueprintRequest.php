<?php

namespace App\Http\Requests\CampaignBlueprint;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignBlueprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'target_audience' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tone' => ['sometimes', 'string', 'max:100'],
            'max_hashtags' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'max_characters' => ['sometimes', 'integer', 'min:1', 'max:280'],
            'additional_rules' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
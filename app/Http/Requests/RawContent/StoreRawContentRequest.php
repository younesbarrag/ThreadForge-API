<?php

namespace App\Http\Requests\RawContent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRawContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campaign_blueprint_id' => ['required', 'integer', Rule::exists('campaign_blueprints', 'id')
                ->where('user_id', $this->user()->id)],

            'content' => ['required', 'string', 'min:20'],
            'source_type' => ['required', 'string', Rule::in(['text', 'markdown', 'readme'])],

        ];
    }
}

<?php

namespace App\Http\Requests\GeneratedPost;

use App\Enums\PublicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneratedPostStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'publication_status' => [
                'required',
                Rule::enum(PublicationStatus::class),
            ],
        ];
    }
}
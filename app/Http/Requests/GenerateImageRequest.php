<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:4000'],
            'size' => ['required', 'string', Rule::in(['1024x1024', '1536x1024', '1024x1536'])],
            'quality' => ['required', 'string', Rule::in(['low', 'medium', 'high'])],
            'reference_images' => ['sometimes', 'array', 'max:4'],
            'reference_images.*' => ['required', 'url'],
        ];
    }
}

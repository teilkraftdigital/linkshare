<?php

namespace App\Http\Requests\Dashboard\BulkActions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MoveBulkBucketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'link_ids' => ['required', 'array', 'min:1'],
            'link_ids.*' => ['integer', 'exists:links,id'],
            'bucket_id' => ['required', 'integer', 'exists:buckets,id'],
        ];
    }
}

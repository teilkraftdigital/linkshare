<?php

namespace App\Http\Requests\Dashboard\BulkActions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RestoreBulkLinksRequest extends FormRequest
{
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
            'link_ids.*' => ['integer', 'exists:links,id,deleted_at,!NULL'],
        ];
    }
}

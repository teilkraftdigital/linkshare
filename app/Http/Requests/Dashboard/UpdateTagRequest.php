<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', 'in:gray,red,orange,amber,yellow,lime,green,teal,cyan,blue,indigo,violet'],
            'is_public' => ['boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:tags,id'],
        ];
    }
}

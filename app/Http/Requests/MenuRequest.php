<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $menuId = $this->route('menu');
        
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('menus')->ignore($menuId)
            ],
            'name' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ];
    }
}

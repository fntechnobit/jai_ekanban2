<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterAreaRequest extends FormRequest
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
        $masterAreaId = $this->route('master_area');

        return [
            'area' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_area')->ignore($masterAreaId)
            ],
            // Aturan urutan print cut off (CO1 & CO2 dulu sebelum CO3-CO5) untuk area ini
            'cutoff_print_rule' => ['required', 'boolean'],
        ];
    }
}

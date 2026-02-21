<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterCarlineRequest extends FormRequest
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
        $masterCarlineId = $this->route('master_carline');

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_carline')->ignore($masterCarlineId)
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'area_id' => [
                'required',
                'exists:master_area,id',
            ],
        ];
    }
}

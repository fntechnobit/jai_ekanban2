<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterMachineRequest extends FormRequest
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
        return [
            'machine' => ['required', 'string', 'max:255'],
            'conveyor_ids' => ['nullable', 'array'],
            'conveyor_ids.*' => ['exists:master_conveyor,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'machine' => 'Machine',
            'conveyor_ids' => 'Conveyor',
        ];
    }
}

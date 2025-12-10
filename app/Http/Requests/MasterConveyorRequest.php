<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterConveyorRequest extends FormRequest
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
            'master_area_id' => ['required', 'exists:master_area,id'],
            'conveyor' => ['required', 'string', 'max:255'],
            'shift_qty' => ['required', 'integer', 'min:1', 'max:3'],
            'capacity' => ['required', 'integer', 'min:1'],
            'pallet_qty' => ['required', 'integer', 'min:1'],
            'family_ids' => ['nullable', 'array'],
            'family_ids.*' => ['exists:master_family,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'master_area_id' => 'Area',
            'conveyor' => 'Conveyor',
            'shift_qty' => 'Shift',
            'capacity' => 'Capacity/Shift',
            'pallet_qty' => 'Pallet Qty',
            'family_ids' => 'Family',
        ];
    }
}

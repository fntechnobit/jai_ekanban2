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
            // Nama conveyor, kapasitas, dan status aktif seluruhnya milik SIREP —
            // tidak ada satu pun yang boleh diubah dari sini. Yang tersisa hanyalah
            // parameter lokal yang tidak dikirim API.
            'master_area_id' => ['nullable', 'exists:master_area,id'],
            'sirep_conveyor_code' => ['nullable', 'string', 'max:50'],
            'pallet_qty' => ['nullable', 'integer', 'min:1'],
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
            'sirep_conveyor_code' => 'Kode Conveyor SIREP',
            'pallet_qty' => 'Pallet Qty',
            'family_ids' => 'Family',
        ];
    }
}

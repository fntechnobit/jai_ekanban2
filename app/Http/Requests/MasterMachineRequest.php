<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'master_area_id' => ['required', 'exists:master_area,id'],
            'conveyor_ids' => ['nullable', 'array'],
            // Conveyor hanya valid bila berada di bawah area yang dipilih.
            'conveyor_ids.*' => [
                Rule::exists('master_conveyor', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where('master_area_id', $this->input('master_area_id'));
                }),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'machine' => 'Machine',
            'master_area_id' => 'Area',
            'conveyor_ids' => 'Conveyor',
            'conveyor_ids.*' => 'Conveyor',
        ];
    }
}

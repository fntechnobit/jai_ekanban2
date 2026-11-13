<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterFamilyRequest extends FormRequest
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
        $masterFamilyId = $this->route('master_family');
        $areaId = $this->input('area_id');

        return [
            'area_id' => [
                'required',
                'exists:master_area,id',
            ],
            // A family name is unique within its area (the same name may exist in another area).
            'family' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_family')
                    ->where(fn ($q) => $q->where('master_area_id', $areaId)->whereNull('deleted_at'))
                    ->ignore($masterFamilyId),
            ],
            // Carline is optional extra info, but must belong to the chosen area.
            'carline_id' => [
                'nullable',
                Rule::exists('master_carline', 'id')->where(fn ($q) => $q->where('area_id', $areaId)),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'area_id' => 'area',
            'carline_id' => 'carline',
        ];
    }
}
